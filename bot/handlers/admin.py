"""Admin handlers — /stats, /broadcast, /users."""
import logging
from telegram import Update
from telegram.ext import (
    ApplicationHandlerStop, CommandHandler, MessageHandler, filters, ContextTypes,
)

from ..database import db
from ..i18n import t
from ..middleware import admin_only, user_lang, register_user

logger = logging.getLogger(__name__)

# Simple broadcast state: set of admin user_ids awaiting broadcast message
_broadcast_state: set[int] = set()


@admin_only
async def cmd_stats(update: Update, context: ContextTypes.DEFAULT_TYPE):
    lang = user_lang(update.effective_user.id)
    stats = db.usage_stats()
    users = db.user_count()
    by_mode = "\n".join(f"  • {m}: {n}" for m, n in stats["by_mode"].items()) or "  (none)"
    by_status = "\n".join(f"  • {s}: {n}" for s, n in stats["by_status"].items()) or "  (none)"
    msg = (
        f"{t(lang, 'stats_title')}\n\n"
        f"👥 Total users: `{users}`\n"
        f"📈 Total generations: `{stats['total']}`\n"
        f"📅 Last 24h: `{stats['today']}` requests · `{stats['active_24h']}` active users\n\n"
        f"📊 By mode:\n{by_mode}\n\n"
        f"✅ By status:\n{by_status}"
    )
    await update.message.reply_text(msg, parse_mode="Markdown")


@admin_only
async def cmd_users(update: Update, context: ContextTypes.DEFAULT_TYPE):
    ids = db.all_user_ids()
    lines = [f"👥 *{len(ids)} users*:\n"]
    for uid in ids[:50]:
        u = db.get_user(uid) or {}
        name = (u.get("first_name") or "") + " " + (u.get("last_name") or "")
        uname = f"@{u['username']}" if u.get("username") else ""
        lines.append(f"`{uid}` {name.strip()} {uname} · {u.get('language','?')}")
    await update.message.reply_text("\n".join(lines), parse_mode="Markdown")


@admin_only
async def cmd_broadcast(update: Update, context: ContextTypes.DEFAULT_TYPE):
    lang = user_lang(update.effective_user.id)
    _broadcast_state.add(update.effective_user.id)
    await update.message.reply_text(t(lang, "broadcast_prompt"))


@admin_only
async def cmd_cancel_broadcast(update: Update, context: ContextTypes.DEFAULT_TYPE):
    _broadcast_state.discard(update.effective_user.id)
    await update.message.reply_text(t(user_lang(update.effective_user.id), "broadcast_cancelled"))


async def handle_broadcast_message(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """If admin is in broadcast state, forward their message to all users."""
    user_id = update.effective_user.id
    if user_id not in _broadcast_state:
        return False
    _broadcast_state.discard(user_id)
    lang = user_lang(user_id)
    ids = db.all_user_ids()
    sent = 0
    for uid in ids:
        try:
            await context.bot.copy_message(chat_id=uid, from_chat_id=update.effective_chat.id,
                                           message_id=update.message.message_id)
            sent += 1
        except Exception as e:
            logger.warning("broadcast fail to %s: %s", uid, e)
    await update.message.reply_text(t(lang, "broadcast_sent", count=sent))
    raise ApplicationHandlerStop()


def register(app):
    app.add_handler(CommandHandler("stats", cmd_stats))
    app.add_handler(CommandHandler("users", cmd_users))
    app.add_handler(CommandHandler("broadcast", cmd_broadcast))
    app.add_handler(CommandHandler("cancel", cmd_cancel_broadcast))
    # Broadcast message catcher (must be before generic text handler)
    app.add_handler(MessageHandler(filters.ALL & ~filters.COMMAND, handle_broadcast_message), group=0)
