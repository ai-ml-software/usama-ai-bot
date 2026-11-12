"""Central configuration loaded from environment variables."""
import os
from dotenv import load_dotenv

load_dotenv()


def _csv_ints(key: str, default: str = "") -> set[int]:
    raw = os.getenv(key, default).strip()
    if not raw:
        return set()
    return {int(x.strip()) for x in raw.split(",") if x.strip().isdigit()}


class Config:
    # Telegram
    BOT_TOKEN: str = os.getenv("TELEGRAM_BOT_TOKEN", "")
    ADMIN_IDS: set[int] = _csv_ints("ADMIN_USER_IDS")
    ALLOWED_IDS: set[int] = _csv_ints("ALLOWED_USER_IDS")  # empty = public

    # Pollinations AI
    AI_BASE_URL: str = os.getenv("POLLINATIONS_BASE_URL", "https://gen.pollinations.ai/v1")
    AI_API_KEY: str = os.getenv("POLLINATIONS_API_KEY", "") or "anonymous"

    # Behavior
    DEFAULT_LANGUAGE: str = os.getenv("DEFAULT_LANGUAGE", "en").lower()
    RATE_LIMIT_SECONDS: float = float(os.getenv("RATE_LIMIT_SECONDS", "5"))
    MAX_HISTORY_TURNS: int = int(os.getenv("MAX_HISTORY_TURNS", "10"))
    DEFAULT_TEXT_MODEL: str = os.getenv("DEFAULT_TEXT_MODEL", "gpt-5.4-mini")
    DEFAULT_IMAGE_MODEL: str = os.getenv("DEFAULT_IMAGE_MODEL", "turbo")
    DEFAULT_TTS_VOICE: str = os.getenv("DEFAULT_TTS_VOICE", "Rachel")

    # Paths
    BASE_DIR: str = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    DATA_DIR: str = os.path.join(BASE_DIR, "data")
    LOG_DIR: str = os.path.join(BASE_DIR, "logs")
    DB_PATH: str = os.path.join(DATA_DIR, "nexus.db")
    TMP_DIR: str = os.path.join(DATA_DIR, "tmp")

    @classmethod
    def ensure_dirs(cls):
        for d in (cls.DATA_DIR, cls.LOG_DIR, cls.TMP_DIR):
            os.makedirs(d, exist_ok=True)

    @classmethod
    def is_admin(cls, user_id: int) -> bool:
        return user_id in cls.ADMIN_IDS

    @classmethod
    def is_allowed(cls, user_id: int) -> bool:
        if not cls.ALLOWED_IDS:
            return True  # public mode
        return user_id in cls.ALLOWED_IDS or cls.is_admin(user_id)
