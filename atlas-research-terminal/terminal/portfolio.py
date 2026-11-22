"""Transactional paper ledger. Orders and account changes commit atomically."""
from __future__ import annotations

import json
import math
import os
import sqlite3
from contextlib import contextmanager
from datetime import datetime, timezone
from pathlib import Path
from zoneinfo import ZoneInfo

UTC = timezone.utc


class Rejected(ValueError):
    pass


class Ledger:
    def __init__(self, path=None):
        self.path = Path(path or os.getenv("ATLAS_DB", "data/atlas.sqlite3"))
        self.path.parent.mkdir(parents=True, exist_ok=True)
        with self.connection() as db:
            db.executescript("""
                PRAGMA journal_mode=WAL;
                CREATE TABLE IF NOT EXISTS accounts (
                    id TEXT PRIMARY KEY, mode TEXT NOT NULL, initial REAL NOT NULL,
                    cash REAL NOT NULL, day TEXT NOT NULL, baseline REAL NOT NULL,
                    paused INTEGER NOT NULL DEFAULT 0, loss_locked INTEGER NOT NULL DEFAULT 0);
                CREATE TABLE IF NOT EXISTS positions (
                    account TEXT NOT NULL, symbol TEXT NOT NULL, qty INTEGER NOT NULL,
                    entry REAL NOT NULL, stop REAL NOT NULL, target REAL NOT NULL,
                    PRIMARY KEY(account, symbol));
                CREATE TABLE IF NOT EXISTS trades (
                    id INTEGER PRIMARY KEY AUTOINCREMENT, account TEXT NOT NULL,
                    request_id TEXT NOT NULL, time TEXT NOT NULL, symbol TEXT NOT NULL,
                    side TEXT NOT NULL, qty INTEGER NOT NULL, price REAL NOT NULL,
                    fee REAL NOT NULL, realized REAL NOT NULL, reason TEXT NOT NULL,
                    source TEXT NOT NULL, quote_time TEXT NOT NULL,
                    UNIQUE(account, request_id));
                CREATE TABLE IF NOT EXISTS snapshots (
                    account TEXT NOT NULL, time TEXT NOT NULL, equity REAL NOT NULL,
                    PRIMARY KEY(account, time));
            """)

    @contextmanager
    def connection(self):
        db = sqlite3.connect(self.path, timeout=15, isolation_level=None)
        db.row_factory = sqlite3.Row
        try:
            yield db
        finally:
            db.close()

    def create(self, account, mode, cash):
        if mode not in {"demo", "yahoo"}:
            raise ValueError("Unknown account mode")
        with self.connection() as db:
            db.execute("INSERT OR IGNORE INTO accounts(id, mode, initial, cash, day, baseline) VALUES(?,?,?,?,?,?)", (account, mode, cash, cash, self.day(), cash))

    @staticmethod
    def day():
        return datetime.now(ZoneInfo("America/New_York")).date().isoformat()

    def read(self, account):
        with self.connection() as db:
            row = db.execute("SELECT * FROM accounts WHERE id=?", (account,)).fetchone()
            if row is None:
                raise ValueError("Account does not exist")
            positions = {r["symbol"]: dict(r) for r in db.execute("SELECT * FROM positions WHERE account=?", (account,))}
            return dict(row), positions

    @staticmethod
    def equity(account, positions, marks):
        return account["cash"] + sum(p["qty"] * marks.get(s, p["entry"]) for s, p in positions.items())

    def mark(self, account, marks, cfg):
        with self.connection() as db:
            db.execute("BEGIN IMMEDIATE")
            try:
                a = dict(db.execute("SELECT * FROM accounts WHERE id=?", (account,)).fetchone())
                pos = {r["symbol"]: dict(r) for r in db.execute("SELECT * FROM positions WHERE account=?", (account,))}
                equity = self.equity(a, pos, marks)
                if a["day"] != self.day():
                    # Carry the previous marked equity into the new day so overnight losses count.
                    previous = db.execute("SELECT equity FROM snapshots WHERE account=? ORDER BY time DESC LIMIT 1", (account,)).fetchone()
                    baseline = previous[0] if previous else equity
                    db.execute("UPDATE accounts SET day=?, baseline=?, loss_locked=0 WHERE id=?", (self.day(), baseline, account))
                    a["baseline"] = baseline
                if equity <= a["baseline"] * (1 - cfg["daily_loss_stop_pct"] / 100):
                    db.execute("UPDATE accounts SET loss_locked=1 WHERE id=?", (account,))
                db.execute("INSERT OR REPLACE INTO snapshots VALUES(?,?,?)", (account, datetime.now(UTC).isoformat(), equity))
                db.commit()
            except Exception:
                db.rollback()
                raise

    def pause(self, account, paused):
        with self.connection() as db:
            db.execute("UPDATE accounts SET paused=? WHERE id=?", (int(paused), account))

    def execute(self, account, side, qty, market, marks, cfg, request_id, reason, session_open=False, mark_times=None):
        if side not in {"BUY", "SELL"} or isinstance(qty, bool) or not isinstance(qty, int) or qty <= 0:
            raise Rejected("Use a positive whole-share quantity and BUY or SELL.")
        price = float(market["price"])
        if not math.isfinite(price) or price <= 0:
            raise Rejected("Quote is invalid.")
        quote_time = datetime.fromisoformat(market["asof"])
        if quote_time.tzinfo is None:
            raise Rejected("Quote timestamp must include a timezone.")
        age = (datetime.now(UTC) - quote_time).total_seconds()
        symbol = market["symbol"]
        # All checks are repeated under the same write lock as the mutation.
        with self.connection() as db:
            db.execute("BEGIN IMMEDIATE")
            try:
                if db.execute("SELECT 1 FROM trades WHERE account=? AND request_id=?", (account, request_id)).fetchone():
                    raise Rejected("This order was already recorded.")
                a = dict(db.execute("SELECT * FROM accounts WHERE id=?", (account,)).fetchone())
                positions = {r["symbol"]: dict(r) for r in db.execute("SELECT * FROM positions WHERE account=?", (account,))}
                if market["source"] != a["mode"]:
                    raise Rejected("Quote source does not match account mode.")
                if age < -60 or age > cfg["max_quote_age_minutes"] * 60:
                    raise Rejected("Quote is stale. Refresh market data before ordering.")
                if a["mode"] == "yahoo" and not session_open:
                    raise Rejected("Market-data paper orders require an open NYSE session.")
                if a["mode"] == "yahoo":
                    from terminal.market import market_session
                    if not market_session()[0]:
                        raise Rejected("Market-data paper orders require an open NYSE session.")
                    for held in positions:
                        timestamp = (mark_times or {}).get(held)
                        if not timestamp:
                            raise Rejected("Every held position needs a fresh timestamped mark.")
                        held_age = (datetime.now(UTC) - datetime.fromisoformat(timestamp)).total_seconds()
                        if held_age < -60 or held_age > cfg["max_quote_age_minutes"] * 60:
                            raise Rejected("A held-position quote is stale. Refresh all market data.")
                if any(s not in marks or not math.isfinite(marks[s]) or marks[s] <= 0 for s in positions):
                    raise Rejected("Every held position needs an available current mark.")
                equity = self.equity(a, positions, marks)
                if a["day"] != self.day():
                    raise Rejected("Refresh portfolio valuation for the new trading day.")
                fill = round(price * (1 + (1 if side == "BUY" else -1) * cfg["slippage_bps"] / 10000), 4)
                fee = cfg["fee_per_order"]
                realized = 0.
                if side == "BUY":
                    if a["paused"] or a["loss_locked"] or equity <= a["baseline"] * (1 - cfg["daily_loss_stop_pct"] / 100):
                        raise Rejected("New buys are paused or the daily loss limit is reached.")
                    if symbol not in positions and len(positions) >= cfg["max_positions"]:
                        raise Rejected("Maximum open positions reached.")
                    old = positions.get(symbol, {"qty": 0, "entry": 0})
                    total_qty = old["qty"] + qty
                    if total_qty * fill > equity * cfg["max_account_pct_per_position"] / 100 + .000001:
                        raise Rejected("Order exceeds the maximum position allocation.")
                    if total_qty * fill * cfg["stop_pct"] / 100 + fee > equity * cfg["risk_pct_per_trade"] / 100:
                        raise Rejected("Order exceeds the stop-distance risk budget.")
                    cost = fill * qty + fee
                    if cost > a["cash"]:
                        raise Rejected("Insufficient paper buying power.")
                    entry = (old["entry"] * old["qty"] + cost) / total_qty
                    db.execute("INSERT OR REPLACE INTO positions VALUES(?,?,?,?,?,?)", (account, symbol, total_qty, entry, entry * (1 - cfg["stop_pct"] / 100), entry * (1 + cfg["take_profit_pct"] / 100)))
                    db.execute("UPDATE accounts SET cash=cash-? WHERE id=?", (cost, account))
                else:
                    old = positions.get(symbol)
                    if not old or qty > old["qty"]:
                        raise Rejected("Cannot sell more shares than you hold.")
                    realized = (fill - old["entry"]) * qty - fee
                    db.execute("UPDATE accounts SET cash=cash+? WHERE id=?", (fill * qty - fee, account))
                    if qty == old["qty"]:
                        db.execute("DELETE FROM positions WHERE account=? AND symbol=?", (account, symbol))
                    else:
                        db.execute("UPDATE positions SET qty=qty-? WHERE account=? AND symbol=?", (qty, account, symbol))
                db.execute("INSERT INTO trades(account,request_id,time,symbol,side,qty,price,fee,realized,reason,source,quote_time) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)", (account, request_id, datetime.now(UTC).isoformat(), symbol, side, qty, fill, fee, realized, reason[:500], market["source"], market["asof"]))
                db.commit()
                return {"fill": fill, "qty": qty, "fee": fee, "realized": realized}
            except Exception:
                db.rollback()
                raise

    def trades(self, account):
        with self.connection() as db:
            return [dict(r) for r in db.execute("SELECT time,symbol,side,qty,price,fee,realized,reason,source,quote_time FROM trades WHERE account=? ORDER BY id DESC", (account,))]

    def history(self, account):
        with self.connection() as db:
            return [dict(r) for r in db.execute("SELECT time,equity FROM snapshots WHERE account=? ORDER BY time", (account,))]


def load_config(path="config.json"):
    with open(path, encoding="utf-8") as file:
        cfg = json.load(file)
    for key in ("paper_cash", "max_account_pct_per_position", "risk_pct_per_trade", "stop_pct", "take_profit_pct", "daily_loss_stop_pct", "max_positions", "news_lookback_hours", "max_quote_age_minutes"):
        value = cfg[key]
        if isinstance(value, bool) or not isinstance(value, (int, float)) or not math.isfinite(value) or value <= 0:
            raise ValueError(f"Invalid positive configuration: {key}")
    for key in ("max_account_pct_per_position", "risk_pct_per_trade", "stop_pct", "daily_loss_stop_pct"):
        if cfg[key] >= 100:
            raise ValueError(f"{key} must be less than 100")
    for key in ("slippage_bps", "fee_per_order"):
        if not math.isfinite(cfg[key]) or cfg[key] < 0:
            raise ValueError(f"{key} must be nonnegative")
    if cfg["slippage_bps"] >= 10000 or int(cfg["max_positions"]) != cfg["max_positions"]:
        raise ValueError("Invalid slippage or position count")
    if not 0 <= cfg["rsi_buy_below"] < cfg["rsi_sell_above"] <= 100:
        raise ValueError("RSI thresholds must be ordered within 0–100")
    if not -1 <= cfg["sentiment_threshold_sell"] < cfg["sentiment_threshold_buy"] <= 1:
        raise ValueError("Sentiment thresholds must be ordered within -1–1")
    return cfg
