"""Video generation handler — /video command and video-mode prompts."""
import logging
import time
from telegram import Update
from telegram.ext import CommandHandler, ContextTypes

from ..database import db
from ..free_media import generate_media
from ..i18n import t
from ..middleware import protected, user_lang, send_upload_video

logger = logging.getLogger(__name__)


async def do_video(update: Update, context: ContextTypes.DEFAULT_TYPE, prompt: str):
    user_id = update.effective_user.id
    lang = user_lang(user_id)
    model = 'auto-free'

    await send_upload_video(context, update.effective_chat.id)
    status = await update.message.reply_text(t(lang, "generating_video"))
    start = time.time()
    try:
        video_file, model = await generate_media('video', prompt, duration=4)
        await update.message.reply_video(video=video_file, caption=f"🎥 {prompt[:180]}\n{model}", supports_streaming=True)
        await status.delete()
        db.log_usage(user_id, "video", model, prompt, "ok", int((time.time() - start) * 1000))
    except Exception as e:
        logger.error("video error: %s", e, exc_info=True)
        db.log_usage(user_id, "video", model, prompt, "error", int((time.time() - start) * 1000))
        await status.edit_text(t(lang, "error_api", detail=str(e)[:200]))


@protected
async def cmd_video(update: Update, context: ContextTypes.DEFAULT_TYPE):
    prompt = " ".join(context.args or []).strip()
    if not prompt:
        await update.message.reply_text(t(user_lang(update.effective_user.id), "empty_prompt"))
        return
    await do_video(update, context, prompt)


def register(app):
    app.add_handler(CommandHandler("video", cmd_video))
