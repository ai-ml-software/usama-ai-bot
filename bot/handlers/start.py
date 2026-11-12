"""Start, help, menu, and models command handlers."""
import logging
from telegram import Update
from telegram.ext import CommandHandler, CallbackQueryHandler, ContextTypes

from ..i18n import t
from ..keyboards import main_menu, settings_menu
from ..middleware import protected, user_lang, register_user
from ..database import db
from ..ai_client import ai
from ..config import Config

logger = logging.getLogger(__name__)


@protected
async def cmd_start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.effective_user.id
    lang = user_lang(user_id)
    await update.message.reply_text(
        t(lang, "welcome"),
        parse_mode="Markdown",
        reply_markup=main_menu(lang),
    )


@protected
async def cmd_help(update: Update, context: ContextTypes.DEFAULT_TYPE):
    await cmd_start(update, context)


@protected
async def cmd_menu(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.effective_user.id
    lang = user_lang(user_id)
    await update.message.reply_text(
        t(lang, "menu_title"),
        parse_mode="Markdown",
        reply_markup=main_menu(lang),
    )


@protected
async def cmd_lang(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Toggle language between en and ar."""
    user_id = update.effective_user.id
    u = db.get_user(user_id) or {}
    new_lang = "ar" if (u.get("language") or "en") == "en" else "en"
    db.set_user_field(user_id, "language", new_lang)
    await update.message.reply_text(
        t(new_lang, "lang_saved") + "\n\n" + t(new_lang, "welcome"),
        parse_mode="Markdown",
        reply_markup=main_menu(new_lang),
    )


@protected
async def cmd_models(update: Update, context: ContextTypes.DEFAULT_TYPE):
    user_id = update.effective_user.id
    lang = user_lang(user_id)
    status = await update.message.reply_text(t(lang, "models_fetching"))
    try:
        models = await ai.list_models(refresh=True)
        lines = [t(lang, "models_title"), ""]
        for m in models[:40]:
            lines.append(f"`{m}`")
        await status.edit_text("\n".join(lines), parse_mode="Markdown")
    except Exception as e:
        logger.error("models error: %s", e)
        await status.edit_text(t(lang, "error_api", detail=str(e)[:200]))


@protected
async def cmd_clear(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Clear conversation history."""
    user_id = update.effective_user.id
    db.clear_history(user_id)
    await update.message.reply_text(t(user_lang(user_id), "history_cleared"))


# ─── Callback query router (menu buttons) ─────────────────────
async def on_callback(update: Update, context: ContextTypes.DEFAULT_TYPE):
    query = update.callback_query
    await query.answer()
    user_id = update.effective_user.id
    register_user(update)
    lang = user_lang(user_id)
    data = query.data or ""

    if data == "menu:main":
        await query.edit_message_text(t(lang, "menu_title"), parse_mode="Markdown",
                                      reply_markup=main_menu(lang))
    elif data.startswith("mode:"):
        mode = data.split(":", 1)[1]
        db.set_user_field(user_id, "mode", mode)
        msg_key = {"chat": "chat_mode_on", "image": "image_mode_on",
                   "audio": "audio_mode_on", "video": "video_mode_on"}.get(mode, "chat_mode_on")
        await query.edit_message_text(t(lang, msg_key), parse_mode="Markdown")
    elif data == "settings:main":
        u = db.get_user(user_id) or {}
        info = (
            f"{t(lang, 'settings_title')}\n\n"
            f"🌐 {t(lang, 'set_language')}: `{u.get('language') or Config.DEFAULT_LANGUAGE}`\n"
            f"🧠 {t(lang, 'set_text_model')}: `{u.get('text_model') or Config.DEFAULT_TEXT_MODEL}`\n"
            f"🎨 {t(lang, 'set_image_model')}: `{u.get('image_model') or Config.DEFAULT_IMAGE_MODEL}`\n"
            f"📐 {t(lang, 'set_image_size')}: `{u.get('image_size') or '1024x1024'}`\n"
            f"🗣️ {t(lang, 'set_voice')}: `{u.get('tts_voice') or Config.DEFAULT_TTS_VOICE}`"
        )
        await query.edit_message_text(info, parse_mode="Markdown", reply_markup=settings_menu(lang))
    elif data == "settings:lang":
        from ..keyboards import language_menu
        await query.edit_message_text(t(lang, "choose_language"), reply_markup=language_menu())
    elif data == "settings:text_model":
        from ..keyboards import text_model_menu
        await query.edit_message_text(t(lang, "choose_text_model"), reply_markup=text_model_menu())
    elif data == "settings:image_model":
        from ..keyboards import image_model_menu
        await query.edit_message_text(t(lang, "choose_image_model"), reply_markup=image_model_menu())
    elif data == "settings:image_size":
        from ..keyboards import image_size_menu
        await query.edit_message_text(t(lang, "choose_image_size"), reply_markup=image_size_menu())
    elif data == "settings:voice":
        from ..keyboards import voice_menu
        await query.edit_message_text(t(lang, "choose_voice"), reply_markup=voice_menu())
    elif data.startswith("set:"):
        # set:<field>:<value>
        parts = data.split(":", 2)
        if len(parts) == 3:
            field, value = parts[1], parts[2]
            db.set_user_field(user_id, field, value)
            if field == "language":
                lang = value
            confirm = {"language": "lang_saved", "text_model": "model_saved",
                       "image_model": "model_saved", "image_size": "size_saved",
                       "tts_voice": "voice_saved"}.get(field, "model_saved")
            await query.edit_message_text(
                t(lang, confirm) + "\n\n" + t(lang, "menu_title"),
                parse_mode="Markdown", reply_markup=main_menu(lang),
            )


def register(app):
    app.add_handler(CommandHandler("start", cmd_start))
    app.add_handler(CommandHandler("help", cmd_help))
    app.add_handler(CommandHandler("menu", cmd_menu))
    app.add_handler(CommandHandler("lang", cmd_lang))
    app.add_handler(CommandHandler("models", cmd_models))
    app.add_handler(CommandHandler("clear", cmd_clear))
    app.add_handler(CallbackQueryHandler(on_callback))
