import numpy as np
import pandas as pd
import pytest

from terminal.backtest import backtest
from terminal.market import demo_market, indicators, market_session, signal
from terminal.portfolio import load_config


def test_rsi_edge_cases():
    for values, expected in [(np.arange(1, 61), 100), (np.arange(60, 0, -1), 0), (np.ones(60), 50)]:
        assert indicators(pd.DataFrame({"Close": values})).RSI.iloc[-1] == expected


def test_demo_reproducible_and_no_news_no_buy():
    first, second = demo_market("AAPL"), demo_market("AAPL")
    pd.testing.assert_frame_equal(first["frame"], second["frame"])
    assert signal(first, [], load_config())["action"] != "BUY"


def test_calendar_holiday_and_early_close():
    assert not market_session(pd.Timestamp("2026-12-25T16:00:00Z"))[0]
    assert market_session(pd.Timestamp("2026-11-27T17:00:00Z"))[0]
    assert not market_session(pd.Timestamp("2026-11-27T19:00:00Z"))[0]


def test_backtest_future_changes_do_not_change_prior_trades():
    cfg = load_config()
    frame = demo_market("MSFT")["frame"]
    _, before = backtest(frame.iloc[:120], cfg)
    modified = frame.copy()
    modified.loc[modified.index[120:], ["Open", "High", "Low", "Close"]] *= 2
    curve, after = backtest(modified, cfg)
    if not before.empty:
        prefix = after[after.date < frame.index[120]].reset_index(drop=True)
        pd.testing.assert_frame_equal(before.reset_index(drop=True), prefix)
    assert np.isfinite(curve.equity).all()
    assert curve.equity.min() > 0


def test_entry_uses_next_open():
    frame = demo_market("AAPL")["frame"].iloc[:55].copy()
    frame["Close"] = 100.
    frame["SMA20"], frame["SMA50"], frame["RSI"] = 95., 90., 50.
    frame["MACD"], frame["MACD_signal"] = 2., 1.
    frame["Open"], frame["High"], frame["Low"] = 110., 111., 109.
    _, trades = backtest(frame, load_config())
    assert trades.iloc[0]["fill"] == pytest.approx(110 * 1.0005)


def test_vader_negation_and_bundled_lexicon():
    from terminal.market import sentiment
    assert sentiment("The outlook is good") > 0
    assert sentiment("The outlook is not good") < 0


def test_entry_bar_stop_is_not_skipped():
    frame = demo_market("AAPL")["frame"].iloc[:51].copy()
    frame["Close"], frame["Open"] = 100., 100.
    frame["SMA20"], frame["SMA50"], frame["RSI"] = 95., 90., 50.
    frame["MACD"], frame["MACD_signal"] = 2., 1.
    frame["Low"], frame["High"] = 80., 130.
    _, trades = backtest(frame, load_config())
    assert list(trades.side) == ["BUY", "SELL"]
    assert trades.iloc[1]["reason"] == "Entry-bar stop"
    assert trades.iloc[1]["realized"] < 0
