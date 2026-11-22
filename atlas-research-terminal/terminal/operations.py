"""Account-scoped scan audit, durable preferences and validated portable recovery."""
from __future__ import annotations

import hashlib
import hmac
import json
import math
import uuid
from datetime import datetime, timezone

from terminal.market import valid_symbol


class Operations:
    def __init__(self, ledger):
        self.ledger = ledger
        with ledger.connection() as db:
            db.executescript('''
                CREATE TABLE IF NOT EXISTS preferences(account TEXT PRIMARY KEY, payload TEXT NOT NULL);
                CREATE TABLE IF NOT EXISTS scans(id TEXT PRIMARY KEY, account TEXT NOT NULL,
                    time TEXT NOT NULL, mode TEXT NOT NULL, config TEXT NOT NULL, results TEXT NOT NULL);
            ''')

    def preferences(self, account, defaults):
        with self.ledger.connection() as db:
            row = db.execute("SELECT payload FROM preferences WHERE account=?", (account,)).fetchone()
        return {**defaults, **(json.loads(row[0]) if row else {})}

    def save_preferences(self, account, symbols, interval):
        if not isinstance(symbols, list) or not 1 <= len(symbols) <= 15 or not all(isinstance(s, str) and valid_symbol(s) for s in symbols):
            raise ValueError("Choose 1–15 valid US tickers.")
        if isinstance(interval, bool) or not isinstance(interval, int) or not 5 <= interval <= 60:
            raise ValueError("Scan interval must be 5–60 minutes.")
        payload = {"symbols": list(dict.fromkeys(symbols)), "scan_interval": interval}
        with self.ledger.connection() as db:
            db.execute("INSERT OR REPLACE INTO preferences VALUES(?,?)", (account, json.dumps(payload)))

    def record_scan(self, account, mode, config, results):
        scan_id = str(uuid.uuid4())
        with self.ledger.connection() as db:
            db.execute("INSERT INTO scans VALUES(?,?,?,?,?,?)", (scan_id, account, datetime.now(timezone.utc).isoformat(), mode, json.dumps(config, allow_nan=False), json.dumps(results, allow_nan=False)))
        return scan_id

    def scans(self, account, limit=30):
        with self.ledger.connection() as db:
            return [{**dict(row), "results": json.loads(row["results"]), "config": json.loads(row["config"])} for row in db.execute("SELECT * FROM scans WHERE account=? ORDER BY time DESC LIMIT ?", (account, limit))]

    @staticmethod
    def checksum(payload):
        return hashlib.sha256(json.dumps(payload, sort_keys=True, separators=(",", ":"), allow_nan=False).encode()).hexdigest()

    def backup(self, account):
        # One read transaction prevents a trade racing the account/position export.
        with self.ledger.connection() as db:
            db.execute("BEGIN")
            row = db.execute("SELECT * FROM accounts WHERE id=?", (account,)).fetchone()
            if row is None:
                raise ValueError("Account not found")
            details = dict(row)
            del details["id"]
            payload = {"account": details}
            for table in ("positions", "trades", "snapshots"):
                order = " ORDER BY id" if table == "trades" else ""
                payload[table] = [{k: v for k, v in dict(r).items() if k not in {"account", "id"}} for r in db.execute(f"SELECT * FROM {table} WHERE account=?{order}", (account,))]
            pref = db.execute("SELECT payload FROM preferences WHERE account=?", (account,)).fetchone()
            payload["preferences"] = json.loads(pref[0]) if pref else None
            db.commit()
        return {"format": "atlas-account", "version": 2, "exported_at": datetime.now(timezone.utc).isoformat(), "checksum": self.checksum(payload), "payload": payload}

    @staticmethod
    def number(value, positive=False):
        if isinstance(value, bool) or not isinstance(value, (int, float)) or not math.isfinite(value) or abs(value) > 1e12 or (positive and value <= 0):
            raise ValueError("Backup contains an invalid numeric value.")
        return value

    @staticmethod
    def timestamp(value):
        if not isinstance(value, str) or datetime.fromisoformat(value).tzinfo is None:
            raise ValueError("Backup timestamps must include a timezone.")
        return value

    def validate_backup(self, backup, expected_mode):
        if backup.get("format") != "atlas-account" or backup.get("version") != 2:
            raise ValueError("Upload an Atlas version 2 account backup.")
        payload = backup["payload"]
        if not hmac.compare_digest(str(backup.get("checksum", "")), self.checksum(payload)):
            raise ValueError("Backup checksum does not match. The file may be damaged.")
        a = payload["account"]
        if a["mode"] not in {"demo", "yahoo"} or a["mode"] != expected_mode:
            raise ValueError("Switch to the backup's workspace mode before restoring.")
        cash = self.number(a["initial"], True)
        self.number(a["baseline"], True)
        self.number(a["cash"])
        datetime.strptime(a["day"], "%Y-%m-%d")
        if a["loss_locked"] not in (0, 1) or a["paused"] not in (0, 1):
            raise ValueError("Invalid account flags.")
        if len(payload["trades"]) > 100000 or len(payload["positions"]) > 1000 or len(payload["snapshots"]) > 100000:
            raise ValueError("Backup exceeds recovery limits.")
        positions, requests = {}, set()
        previous_time = ""
        for t in payload["trades"]:
            symbol = t["symbol"]
            if not isinstance(symbol, str) or not valid_symbol(symbol) or t["source"] != a["mode"] or t["side"] not in {"BUY", "SELL"}:
                raise ValueError("Invalid trade symbol, side or source.")
            qty = t["qty"]
            if isinstance(qty, bool) or not isinstance(qty, int) or qty <= 0:
                raise ValueError("Trade quantities must be positive whole shares.")
            price, fee = self.number(t["price"], True), self.number(t["fee"])
            if fee < 0 or not isinstance(t["reason"], str) or len(t["reason"]) > 500:
                raise ValueError("Invalid fee or decision note.")
            self.timestamp(t["time"])
            self.timestamp(t["quote_time"])
            if previous_time and datetime.fromisoformat(t["time"]) < datetime.fromisoformat(previous_time):
                raise ValueError("Trade history is out of order.")
            previous_time = t["time"]
            request = t["request_id"]
            if not isinstance(request, str) or not request or len(request) > 200 or request in requests:
                raise ValueError("Invalid or repeated order identifier.")
            requests.add(request)
            old = positions.get(symbol, {"qty": 0, "entry": 0.})
            if t["side"] == "BUY":
                total = old["qty"] + qty
                positions[symbol] = {"qty": total, "entry": (old["qty"] * old["entry"] + qty * price + fee) / total}
                cash -= qty * price + fee
                realized = 0.
            else:
                if qty > old["qty"]:
                    raise ValueError("Trade history sells unowned shares.")
                realized = (price - old["entry"]) * qty - fee
                cash += qty * price - fee
                if qty == old["qty"]:
                    del positions[symbol]
                else:
                    positions[symbol]["qty"] -= qty
            if not math.isclose(self.number(t["realized"]), realized, abs_tol=.01):
                raise ValueError("Realized P&L does not reconcile with trade history.")
        if not math.isclose(cash, a["cash"], abs_tol=.01):
            raise ValueError("Cash does not reconcile with trade history.")
        seen = set()
        for p in payload["positions"]:
            symbol = p["symbol"]
            if symbol in seen or symbol not in positions:
                raise ValueError("Holdings do not reconcile with trade history.")
            seen.add(symbol)
            old = positions[symbol]
            if p["qty"] != old["qty"] or not math.isclose(self.number(p["entry"], True), old["entry"], abs_tol=.0001):
                raise ValueError("Position quantity or cost does not reconcile.")
            if not 0 < self.number(p["stop"], True) < p["entry"] < self.number(p["target"], True):
                raise ValueError("Invalid stored stop or target.")
        if seen != set(positions):
            raise ValueError("Some holdings are missing from the backup.")
        snapshot_times = set()
        for snapshot in payload["snapshots"]:
            self.timestamp(snapshot["time"])
            if snapshot["time"] in snapshot_times:
                raise ValueError("Repeated equity snapshot timestamp.")
            snapshot_times.add(snapshot["time"])
            self.number(snapshot["equity"])
        prefs = payload.get("preferences")
        if prefs is not None:
            symbols, interval = prefs["symbols"], prefs["scan_interval"]
            if not isinstance(symbols, list) or not 1 <= len(symbols) <= 15 or not all(isinstance(s, str) and valid_symbol(s) for s in symbols) or isinstance(interval, bool) or not isinstance(interval, int) or not 5 <= interval <= 60:
                raise ValueError("Invalid saved scan preferences.")
        return payload

    def restore(self, backup, expected_mode):
        try:
            payload = self.validate_backup(backup, expected_mode)
        except (KeyError, TypeError, OverflowError, AttributeError) as exc:
            raise ValueError("Backup is missing required fields or has invalid types.") from exc
        a = payload["account"]
        account = f"{uuid.uuid4()}:{expected_mode}"
        with self.ledger.connection() as db:
            db.execute("BEGIN IMMEDIATE")
            try:
                # Recovery always creates a new, paused account; it never replaces one.
                db.execute("INSERT INTO accounts VALUES(?,?,?,?,?,?,?,?)", (account, expected_mode, a["initial"], a["cash"], a["day"], a["baseline"], 1, a["loss_locked"]))
                for p in payload["positions"]:
                    db.execute("INSERT INTO positions VALUES(?,?,?,?,?,?)", (account, p["symbol"], p["qty"], p["entry"], p["stop"], p["target"]))
                for t in payload["trades"]:
                    columns = ["request_id", "time", "symbol", "side", "qty", "price", "fee", "realized", "reason", "source", "quote_time"]
                    db.execute("INSERT INTO trades(account," + ",".join(columns) + ") VALUES(" + ",".join("?" for _ in range(12)) + ")", (account, *[t[c] for c in columns]))
                for s in payload["snapshots"]:
                    db.execute("INSERT INTO snapshots VALUES(?,?,?)", (account, s["time"], s["equity"]))
                if payload.get("preferences"):
                    db.execute("INSERT INTO preferences VALUES(?,?)", (account, json.dumps(payload["preferences"])))
                db.commit()
            except Exception:
                db.rollback()
                raise
        return account
