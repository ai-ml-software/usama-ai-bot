"""Nexus AI Bot — main entry point.

Usage:
    python -m bot.main          (from project root)
    python bot/main.py
"""
import logging
import os
import sys
from logging.handlers import RotatingFileHandler

# Ensure project root on path
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from bot.config import Config
from bot import __app_name__, __version__


def setup_logging():
    Config.ensure_dirs()
    fmt = "%(asctime)s [%(levelname)-7s] %(name)s: %(message)s"
    handlers = [logging.StreamHandler(sys.stdout)]
    log_file = os.path.join(Config.LOG_DIR, "bot.log")
    handlers.append(RotatingFileHandler(log_file, maxBytes=5 * 1024 * 1024, backupCount=3, encoding="utf-8"))
    logging.basicConfig(level=logging.INFO, format=fmt, handlers=handlers)


def main():
    setup_logging()
    logger = logging.getLogger("nexus")

    if not Config.BOT_TOKEN:
        logger.error("TELEGRAM_BOT_TOKEN not set. Run `python setup.py` first.")
        sys.exit(1)

    from telegram.ext import Application
    from bot.handlers import start, chat, image, audio, video, admin

    app = Application.builder().token(Config.BOT_TOKEN).build()

    # Register handlers — admin FIRST so broadcast catcher runs before chat text handler
    admin.register(app)
    start.register(app)
    image.register(app)
    audio.register(app)
    video.register(app)
    chat.register(app)  # generic text/voice/photo handlers last

    logger.info("=" * 60)
    logger.info("%s v%s starting…", __app_name__, __version__)
    logger.info("Admins: %s | Allowed: %s",
                Config.ADMIN_IDS or "(none — public)", Config.ALLOWED_IDS or "(public)")
    logger.info("AI endpoint: %s", Config.AI_BASE_URL)
    logger.info("=" * 60)

    app.run_polling(drop_pending_updates=True, allowed_updates=["message", "callback_query"])


if __name__ == "__main__":
    main()
