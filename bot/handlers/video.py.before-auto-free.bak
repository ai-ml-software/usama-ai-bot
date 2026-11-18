"""Video generation handler — /video command and video-mode prompts."""
import logging
import time
from telegram import Update
from telegram.ext import CommandHandler, ContextTypes

from ..database import db
from ..ai_client import ai, VIDEO_MODELS
from ..i18n import t
from ..middleware import protected, user_lang, send_upload_video

logger = logging.getLogger(__name__)


async def do_video(update: Update, context: ContextTypes.DEFAULT_TYPE, prompt: str):
    user_id = update.effective_user.id
    lang = user_lang(user_id)
    model = VIDEO_MODELS[0]

    await send_upload_video(context, update.effective_chat.id)
    status = await update.message.reply_text(t(lang, "generating_video"))
    start = time.time()
    try:
        url = await ai.generate_video(prompt, model=model)
        await status.delete()
        try:
            await update.message.reply_video(video=url, caption=f"🎥 {prompt[:180]}\n`{model}`",
                                             parse_mode="Markdown", supports_streaming=True)
        except Exception:
            # Fallback: send as document if video upload fails
            await update.message.reply_document(document=url, caption=f"🎥 {prompt[:180]}")
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
