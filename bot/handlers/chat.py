"""Text chat handler with conversation memory and streaming output."""
import logging
import time
from telegram import Update
from telegram.ext import MessageHandler, filters, ContextTypes

from ..config import Config
from ..database import db
from ..ai_client import ai
from ..i18n import t
from ..middleware import protected, user_lang, send_typing

logger = logging.getLogger(__name__)

SYSTEM_PROMPT = (
    "You are Nexus AI, a helpful, friendly, and concise AI assistant inside Telegram. "
    "Answer clearly. Use Markdown formatting sparingly. If asked about your capabilities, "
    "mention you can generate text, images (/image), audio (/audio), and video (/video)."
)


async def _stream_reply(update: Update, context, prompt: str, user_id: int, lang: str):
    """Stream AI response, editing the status message as tokens arrive."""
    u = db.get_user(user_id) or {}
    model = u.get("text_model") or Config.DEFAULT_TEXT_MODEL

    history = db.get_history(user_id)
    messages = [{"role": "system", "content": SYSTEM_PROMPT}] + history + [
        {"role": "user", "content": prompt}
    ]

    status = await update.message.reply_text(t(lang, "thinking"))
    start = time.time()
    try:
        full = ""
        last_edit = time.time()
        async for token in ai.chat_stream(messages, model=model, max_tokens=1024):
            full += token
            # Throttle edits to ~once per 1.5s to avoid Telegram rate limits
            if time.time() - last_edit > 1.5 and len(full) > 20:
                try:
                    await status.edit_text(full[:4000])
                    last_edit = time.time()
                except Exception:
                    pass
        if not full.strip():
            full = "(empty response)"
        await status.edit_text(full[:4096])
        db.add_message(user_id, "user", prompt, model)
        db.add_message(user_id, "assistant", full, model)
        db.log_usage(user_id, "text", model, prompt, "ok", int((time.time() - start) * 1000))
    except Exception as e:
        logger.error("chat error: %s", e, exc_info=True)
        db.log_usage(user_id, "text", model, prompt, "error", int((time.time() - start) * 1000))
        await status.edit_text(t(lang, "error_api", detail=str(e)[:200]))


@protected
async def handle_text(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Route plain text based on user's current mode (chat/image/audio/video)."""
    user_id = update.effective_user.id
    lang = user_lang(user_id)
    text = update.message.text or ""
    if not text or text.startswith("/"):
        return

    u = db.get_user(user_id) or {}
    mode = u.get("mode") or "chat"

    if mode == "image":
        from .image import do_image
        await do_image(update, context, text)
    elif mode == "audio":
        from .audio import do_audio
        await do_audio(update, context, text)
    elif mode == "video":
        from .video import do_video
        await do_video(update, context, text)
    else:
        await send_typing(context, update.effective_chat.id)
        await _stream_reply(update, context, text, user_id, lang)


@protected
async def handle_voice(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Transcribe voice message via STT and reply with AI text."""
    user_id = update.effective_user.id
    lang = user_lang(user_id)
    status = await update.message.reply_text(t(lang, "processing_voice"))
    try:
        voice = update.message.voice
        file = await context.bot.get_file(voice.file_id)
        import os, tempfile
        tmp = os.path.join(Config.TMP_DIR, f"voice_{user_id}_{int(time.time())}.ogg")
        await file.download_to_drive(tmp)

        # Transcribe via Pollinations Whisper-compatible endpoint
        with open(tmp, "rb") as f:
            transcript = await ai.client.audio.transcriptions.create(
                model="whisper-1", file=f,
            )
        text = transcript.text
        os.remove(tmp)

        await status.edit_text(t(lang, "voice_reply_prefix", transcript=text[:200]) + t(lang, "thinking"),
                               parse_mode="Markdown")
        await _stream_reply(update, context, text, user_id, lang)
        # Replace the "thinking" status — _stream_reply creates its own status; delete ours
        await status.delete()
    except Exception as e:
        logger.error("voice error: %s", e, exc_info=True)
        await status.edit_text(t(lang, "error_api", detail=str(e)[:200]))


@protected
async def handle_photo(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Store photo; if caption present, treat as image-edit/describe request."""
    user_id = update.effective_user.id
    lang = user_lang(user_id)
    photo = update.message.photo[-1]  # largest
    caption = update.message.caption or ""
    db.set_pending_photo(user_id, photo.file_id, caption)
    if caption:
        # Treat caption as prompt for image analysis/edit
        from .image import do_image_with_photo
        await do_image_with_photo(update, context, caption, photo.file_id)
    else:
        await update.message.reply_text(t(lang, "photo_received"))


def register(app):
    app.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, handle_text))
    app.add_handler(MessageHandler(filters.VOICE, handle_voice))
    app.add_handler(MessageHandler(filters.PHOTO, handle_photo))
