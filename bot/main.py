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
    # Suppress Telegram token-bearing URLs in HTTP client logs.
    logging.getLogger('httpx').setLevel(logging.WARNING)
    logging.getLogger('httpcore').setLevel(logging.WARNING)



def main():
    setup_logging()
    logger = logging.getLogger("nexus")

    if not Config.BOT_TOKEN:
        logger.error("TELEGRAM_BOT_TOKEN not set. Run `python setup.py` first.")
        sys.exit(1)

    # USAMA_CONFLICT_FIX_20261118
    # Advisory lock is host-local. It does NOT coordinate separate servers.
    import fcntl
    from telegram.error import Conflict
    lock_file = open(os.path.join(Config.DATA_DIR, 'telegram_poller.lock'), 'a+b')
    try:
        fcntl.flock(lock_file.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
    except BlockingIOError:
        logger.error('Another local bot poller already holds the lock; refusing to start.')
        raise SystemExit(78)
    conflict_seen = {'value': False}

    async def on_error(update, context):
        error = context.error
        if isinstance(error, Conflict):
            if not conflict_seen['value']:
                logger.critical('Telegram polling conflict (409): another process uses this token. Stop duplicate pollers or rotate the token.')
                conflict_seen['value'] = True
                context.application.stop_running()
            return
        # Avoid rendering potentially secret-bearing exception details in logs.
        logger.error('Telegram handler failed: %s', type(error).__name__)

    from telegram.ext import Application
    from bot.handlers import start, chat, image, audio, video, admin

    app = Application.builder().token(Config.BOT_TOKEN).build()
    app.add_error_handler(on_error)

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

    # Preserve queued updates across restarts so incoming messages are not discarded.
    try:
        app.run_polling(
            drop_pending_updates=False,
            allowed_updates=['message', 'callback_query'],
            bootstrap_retries=0,
        )
    finally:
        fcntl.flock(lock_file.fileno(), fcntl.LOCK_UN)
        lock_file.close()
    if conflict_seen['value']:
        raise SystemExit(78)  # Supervisor must NOT restart a 409 conflict.



if __name__ == "__main__":
    main()
