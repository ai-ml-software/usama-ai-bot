"""Middleware: authentication, rate limiting, typing indicator, user tracking."""
import time
import logging
from functools import wraps
from typing import Callable

from telegram import Update
from telegram.ext import ContextTypes

from .config import Config
from .database import db
from .i18n import t

logger = logging.getLogger(__name__)

_last_request: dict[int, float] = {}


def user_lang(user_id: int) -> str:
    u = db.get_user(user_id)
    return (u or {}).get("language") or Config.DEFAULT_LANGUAGE


def register_user(update: Update) -> dict:
    """Ensure user exists in DB; return user record."""
    eu = update.effective_user
    return db.upsert_user(
        user_id=eu.id,
        username=eu.username or "",
        first_name=eu.first_name or "",
        last_name=eu.last_name or "",
        language=(eu.language_code or "")[:2],
    )


def check_rate_limit(user_id: int) -> bool:
    """Return True if limited."""
    now = time.time()
    last = _last_request.get(user_id, 0)
    if now - last < Config.RATE_LIMIT_SECONDS:
        return True
    _last_request[user_id] = now
    return False


def protected(handler: Callable):
    """Decorator: auth + rate-limit + user registration for command/message handlers."""
    @wraps(handler)
    async def wrapper(update: Update, context: ContextTypes.DEFAULT_TYPE, *args, **kwargs):
        user_id = update.effective_user.id
        register_user(update)
        lang = user_lang(user_id)
        if not Config.is_allowed(user_id):
            await update.effective_message.reply_text(t(lang, "not_allowed"))
            return
        if check_rate_limit(user_id):
            await update.effective_message.reply_text(t(lang, "rate_limited"))
            return
        return await handler(update, context, *args, **kwargs)
    return wrapper


def admin_only(handler: Callable):
    """Decorator: restrict to admin users."""
    @wraps(handler)
    async def wrapper(update: Update, context: ContextTypes.DEFAULT_TYPE, *args, **kwargs):
        user_id = update.effective_user.id
        register_user(update)
        if not Config.is_admin(user_id):
            await update.effective_message.reply_text(t(user_lang(user_id), "admin_only"))
            return
        return await handler(update, context, *args, **kwargs)
    return wrapper


async def send_typing(context: ContextTypes.DEFAULT_TYPE, chat_id: int):
    """Send typing action (call before long operations)."""
    await context.bot.send_chat_action(chat_id=chat_id, action="typing")


async def send_upload_photo(context: ContextTypes.DEFAULT_TYPE, chat_id: int):
    await context.bot.send_chat_action(chat_id=chat_id, action="upload_photo")


async def send_upload_audio(context: ContextTypes.DEFAULT_TYPE, chat_id: int):
    await context.bot.send_chat_action(chat_id=chat_id, action="upload_voice")


async def send_upload_video(context: ContextTypes.DEFAULT_TYPE, chat_id: int):
    await context.bot.send_chat_action(chat_id=chat_id, action="upload_video")
