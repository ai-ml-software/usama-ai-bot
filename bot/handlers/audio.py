"""Audio (TTS) handler — /audio command and audio-mode prompts."""
import logging
import os
import time
from telegram import Update
from telegram.ext import CommandHandler, ContextTypes

from ..config import Config
from ..database import db
from ..ai_client import ai
from ..i18n import t
from ..middleware import protected, user_lang, send_upload_audio

logger = logging.getLogger(__name__)


async def do_audio(update: Update, context: ContextTypes.DEFAULT_TYPE, text: str):
    user_id = update.effective_user.id
    lang = user_lang(user_id)
    u = db.get_user(user_id) or {}
    voice = u.get("tts_voice") or Config.DEFAULT_TTS_VOICE

    await send_upload_audio(context, update.effective_chat.id)
    status = await update.message.reply_text(t(lang, "generating_audio"))
    start = time.time()
    out_path = os.path.join(Config.TMP_DIR, f"tts_{user_id}_{int(time.time())}.mp3")
    try:
        await ai.generate_speech(text, voice=voice, out_path=out_path)
        await status.delete()
        with open(out_path, "rb") as f:
            await update.message.reply_audio(audio=f, title="Nexus AI TTS",
                                             performer=voice, caption=f"🗣️ {text[:180]}")
        db.log_usage(user_id, "audio", f"elevenlabs/{voice}", text, "ok", int((time.time() - start) * 1000))
    except Exception as e:
        logger.error("audio error: %s", e, exc_info=True)
        db.log_usage(user_id, "audio", f"elevenlabs/{voice}", text, "error", int((time.time() - start) * 1000))
        await status.edit_text(t(lang, "error_api", detail=str(e)[:200]))
    finally:
        if os.path.exists(out_path):
            try:
                os.remove(out_path)
            except OSError:
                pass


@protected
async def cmd_audio(update: Update, context: ContextTypes.DEFAULT_TYPE):
    text = " ".join(context.args or []).strip()
    if not text:
        await update.message.reply_text(t(user_lang(update.effective_user.id), "empty_prompt"))
        return
    await do_audio(update, context, text)


def register(app):
    app.add_handler(CommandHandler("audio", cmd_audio))
