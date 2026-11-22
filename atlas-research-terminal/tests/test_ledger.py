from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timedelta, timezone

import pytest

from terminal.market import demo_market
from terminal.portfolio import Ledger, Rejected, load_config


@pytest.fixture
def setup(tmp_path):
    ledger = Ledger(tmp_path / "test.sqlite3")
    cfg = load_config()
    ledger.create("test", "demo", cfg["paper_cash"])
    quote = {**demo_market("AAPL"), "price": 100.0}
    return ledger, cfg, quote


def order(setup, side="BUY", qty=10, token="order1", price=None):
    ledger, cfg, quote = setup
    if price is not None:
        quote = {**quote, "price": price}
    return ledger.execute("test", side, qty, quote, {"AAPL": quote["price"]}, cfg, token, "Test reason")


def test_roundtrip_fees_partial_and_persistence(setup):
    ledger, cfg, quote = setup
    buy = order(setup)
    a, p = ledger.read("test")
    assert a["cash"] == pytest.approx(100000 - 100.05 * 10 - 1)
    assert p["AAPL"]["entry"] == pytest.approx(100.15)
    assert p["AAPL"]["stop"] == pytest.approx(100.15 * .93)
    sold = order(setup, "SELL", 4, "partial", 110)
    assert sold["realized"] == pytest.approx((109.945 - 100.15) * 4 - 1)
    assert Ledger(ledger.path).read("test")[1]["AAPL"]["qty"] == 6
    order(setup, "SELL", 6, "final", 110)
    assert not ledger.read("test")[1]
    assert len(ledger.trades("test")) == 3
    assert ledger.read("test")[0]["cash"] - 100000 == pytest.approx(sum(t["realized"] for t in ledger.trades("test")))


@pytest.mark.parametrize("qty", [0, -1, 1.5, True])
def test_invalid_quantity(setup, qty):
    with pytest.raises(Rejected): order(setup, qty=qty)


def test_allocation_and_duplicates_are_atomic(setup):
    ledger, cfg, quote = setup
    with pytest.raises(Rejected, match="allocation"): order(setup, qty=81)
    assert not ledger.trades("test")
    order(setup)
    with pytest.raises(Rejected, match="already"): order(setup)
    assert ledger.read("test")[1]["AAPL"]["qty"] == 10


def test_no_short_selling(setup):
    with pytest.raises(Rejected, match="more shares"): order(setup, "SELL")
    order(setup)
    with pytest.raises(Rejected): order(setup, "SELL", 11, "oversell")


def test_pause_allows_exits(setup):
    ledger, _, _ = setup
    order(setup)
    ledger.pause("test", True)
    with pytest.raises(Rejected, match="paused"): order(setup, token="paused")
    order(setup, "SELL", 10, "exit")


def test_risk_limit(setup):
    ledger, cfg, quote = setup
    cfg["risk_pct_per_trade"] = .1
    with pytest.raises(Rejected, match="risk budget"): order(setup, qty=20)


def test_stale_and_cross_source_rejected(setup):
    ledger, cfg, quote = setup
    quote["asof"] = (datetime.now(timezone.utc) - timedelta(hours=1)).isoformat()
    with pytest.raises(Rejected, match="stale"): order(setup)
    quote["asof"] = datetime.now(timezone.utc).isoformat()
    quote["source"] = "yahoo"
    with pytest.raises(Rejected, match="source"): order(setup)


def test_market_closed(setup):
    ledger, cfg, quote = setup
    ledger.create("live", "yahoo", 100000)
    quote["source"] = "yahoo"
    with pytest.raises(Rejected, match="open NYSE"):
        ledger.execute("live", "BUY", 1, quote, {}, cfg, "x", "test")


def test_loss_gate_latches_until_next_day(setup):
    ledger, cfg, _ = setup
    order(setup, qty=70)
    ledger.mark("test", {"AAPL": 20}, cfg)
    assert ledger.read("test")[0]["loss_locked"]
    ledger.mark("test", {"AAPL": 100}, cfg)
    with pytest.raises(Rejected, match="daily loss"): order(setup, qty=1, token="locked")


def test_new_day_counts_overnight_loss(setup):
    ledger, cfg, _ = setup
    order(setup, qty=70)
    ledger.mark("test", {"AAPL": 100}, cfg)
    with ledger.connection() as db:
        db.execute("UPDATE accounts SET day='2000-01-01'")
    ledger.mark("test", {"AAPL": 20}, cfg)
    assert ledger.read("test")[0]["loss_locked"]


def test_concurrent_duplicate_order(setup):
    def submit(_):
        try:
            order(setup)
            return "filled"
        except Rejected:
            return "rejected"
    with ThreadPoolExecutor(max_workers=4) as pool:
        results = list(pool.map(submit, range(4)))
    assert results.count("filled") == 1
    assert len(setup[0].trades("test")) == 1


def test_account_isolation(setup):
    ledger, _, _ = setup
    ledger.create("other", "demo", 100000)
    order(setup)
    assert ledger.read("other")[1] == {}
    assert ledger.trades("other") == []


def test_existing_holding_requires_fresh_mark(setup, monkeypatch):
    ledger, cfg, quote = setup
    monkeypatch.setattr("terminal.market.market_session", lambda: (True, "Open"))
    ledger.create("market", "yahoo", 100000)
    quote = {**quote, "source": "yahoo"}
    ledger.execute("market", "BUY", 1, quote, {}, cfg, "first", "test", session_open=True)
    stale = (datetime.now(timezone.utc) - timedelta(hours=1)).isoformat()
    with pytest.raises(Rejected, match="held-position quote is stale"):
        ledger.execute("market", "BUY", 1, quote, {"AAPL": 100}, cfg, "second", "test", session_open=True, mark_times={"AAPL": stale})


def test_position_additions_respect_aggregate_cap(setup):
    order(setup, qty=60)
    with pytest.raises(Rejected, match="allocation"):
        order(setup, qty=30, token="addition")
    assert setup[0].read("test")[1]["AAPL"]["qty"] == 60
