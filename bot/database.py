"""SQLite database layer — users, conversation history, usage logs."""
import json
import sqlite3
import time
from contextlib import contextmanager
from typing import Optional

from .config import Config


SCHEMA = """
CREATE TABLE IF NOT EXISTS users (
    user_id     INTEGER PRIMARY KEY,
    username    TEXT,
    first_name  TEXT,
    last_name   TEXT,
    language    TEXT DEFAULT 'en',
    mode        TEXT DEFAULT 'chat',
    text_model  TEXT DEFAULT '',
    image_model TEXT DEFAULT '',
    image_size  TEXT DEFAULT '1024x1024',
    tts_voice   TEXT DEFAULT '',
    created_at  REAL,
    last_active REAL,
    block_reason TEXT DEFAULT ''
);

CREATE TABLE IF NOT EXISTS conversations (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER,
    role       TEXT,
    content    TEXT,
    model      TEXT,
    created_at REAL
);
CREATE INDEX IF NOT EXISTS idx_conv_user ON conversations(user_id, created_at);

CREATE TABLE IF NOT EXISTS usage_logs (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER,
    mode        TEXT,
    model       TEXT,
    prompt_preview TEXT,
    status      TEXT,
    duration_ms INTEGER,
    created_at  REAL
);
CREATE INDEX IF NOT EXISTS idx_usage_user ON usage_logs(user_id, created_at);

CREATE TABLE IF NOT EXISTS pending_photo (
    user_id    INTEGER PRIMARY KEY,
    file_id    TEXT,
    caption    TEXT,
    created_at REAL
);
"""


class Database:
    def __init__(self, path: str):
        self.path = path
        self._init_db()

    @contextmanager
    def _conn(self):
        conn = sqlite3.connect(self.path, timeout=30)
        conn.row_factory = sqlite3.Row
        try:
            yield conn
            conn.commit()
        finally:
            conn.close()

    def _init_db(self):
        with self._conn() as c:
            c.executescript(SCHEMA)

    # ─── Users ──────────────────────────────────────────────
    def upsert_user(self, user_id: int, username: str = "", first_name: str = "",
                    last_name: str = "", language: str = "") -> dict:
        now = time.time()
        with self._conn() as c:
            row = c.execute("SELECT * FROM users WHERE user_id=?", (user_id,)).fetchone()
            if row:
                c.execute(
                    "UPDATE users SET username=?, first_name=?, last_name=?, last_active=? WHERE user_id=?",
                    (username, first_name, last_name, now, user_id),
                )
                if language and not row["language"]:
                    c.execute("UPDATE users SET language=? WHERE user_id=?", (language, user_id))
                return dict(c.execute("SELECT * FROM users WHERE user_id=?", (user_id,)).fetchone())
            lang = language or Config.DEFAULT_LANGUAGE
            c.execute(
                """INSERT INTO users (user_id, username, first_name, last_name, language,
                   created_at, last_active) VALUES (?,?,?,?,?,?,?)""",
                (user_id, username, first_name, last_name, lang, now, now),
            )
            return dict(c.execute("SELECT * FROM users WHERE user_id=?", (user_id,)).fetchone())

    def get_user(self, user_id: int) -> Optional[dict]:
        with self._conn() as c:
            row = c.execute("SELECT * FROM users WHERE user_id=?", (user_id,)).fetchone()
            return dict(row) if row else None

    def set_user_field(self, user_id: int, field: str, value: str):
        allowed = {"language", "mode", "text_model", "image_model", "image_size", "tts_voice"}
        if field not in allowed:
            return
        with self._conn() as c:
            c.execute(f"UPDATE users SET {field}=? WHERE user_id=?", (value, user_id))

    def all_user_ids(self) -> list[int]:
        with self._conn() as c:
            return [r["user_id"] for r in c.execute("SELECT user_id FROM users").fetchall()]

    def user_count(self) -> int:
        with self._conn() as c:
            return c.execute("SELECT COUNT(*) FROM users").fetchone()[0]

    # ─── Conversation history ───────────────────────────────
    def add_message(self, user_id: int, role: str, content: str, model: str = ""):
        with self._conn() as c:
            c.execute(
                "INSERT INTO conversations (user_id, role, content, model, created_at) VALUES (?,?,?,?,?)",
                (user_id, role, content, model, time.time()),
            )
            # Prune old messages: keep last MAX_HISTORY_TURNS*2
            c.execute(
                """DELETE FROM conversations WHERE user_id=? AND id NOT IN (
                   SELECT id FROM conversations WHERE user_id=? ORDER BY created_at DESC LIMIT ?)""",
                (user_id, user_id, Config.MAX_HISTORY_TURNS * 2),
            )

    def get_history(self, user_id: int) -> list[dict]:
        with self._conn() as c:
            rows = c.execute(
                "SELECT role, content FROM conversations WHERE user_id=? ORDER BY created_at ASC",
                (user_id,),
            ).fetchall()
            return [{"role": r["role"], "content": r["content"]} for r in rows]

    def clear_history(self, user_id: int):
        with self._conn() as c:
            c.execute("DELETE FROM conversations WHERE user_id=?", (user_id,))

    # ─── Usage logs ─────────────────────────────────────────
    def log_usage(self, user_id: int, mode: str, model: str, prompt: str,
                  status: str, duration_ms: int):
        with self._conn() as c:
            c.execute(
                """INSERT INTO usage_logs (user_id, mode, model, prompt_preview, status,
                   duration_ms, created_at) VALUES (?,?,?,?,?,?,?)""",
                (user_id, mode, model, (prompt or "")[:200], status, duration_ms, time.time()),
            )

    def usage_stats(self) -> dict:
        with self._conn() as c:
            total = c.execute("SELECT COUNT(*) FROM usage_logs").fetchone()[0]
            by_mode = {r["mode"]: r["n"] for r in c.execute(
                "SELECT mode, COUNT(*) n FROM usage_logs GROUP BY mode").fetchall()}
            by_status = {r["status"]: r["n"] for r in c.execute(
                "SELECT status, COUNT(*) n FROM usage_logs GROUP BY status").fetchall()}
            today = c.execute(
                "SELECT COUNT(*) FROM usage_logs WHERE created_at > ?",
                (time.time() - 86400,)).fetchone()[0]
            active_24h = c.execute(
                "SELECT COUNT(DISTINCT user_id) FROM usage_logs WHERE created_at > ?",
                (time.time() - 86400,)).fetchone()[0]
            return {"total": total, "by_mode": by_mode, "by_status": by_status,
                    "today": today, "active_24h": active_24h}

    # ─── Pending photo (for image edit / describe) ──────────
    def set_pending_photo(self, user_id: int, file_id: str, caption: str = ""):
        with self._conn() as c:
            c.execute("INSERT OR REPLACE INTO pending_photo (user_id, file_id, caption, created_at) VALUES (?,?,?,?)",
                      (user_id, file_id, caption, time.time()))

    def pop_pending_photo(self, user_id: int) -> Optional[dict]:
        with self._conn() as c:
            row = c.execute("SELECT * FROM pending_photo WHERE user_id=?", (user_id,)).fetchone()
            if row:
                c.execute("DELETE FROM pending_photo WHERE user_id=?", (user_id,))
                return dict(row)
            return None


Config.ensure_dirs()
db = Database(Config.DB_PATH)
