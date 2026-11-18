"""Image generation handler — /image command and image-mode prompts."""
import logging
import time
from telegram import Update
from telegram.ext import CommandHandler, ContextTypes

from ..config import Config
from ..database import db
from ..ai_client import ai
from ..i18n import t
from ..middleware import protected, user_lang, send_upload_photo

logger = logging.getLogger(__name__)


async def do_image(update: Update, context: ContextTypes.DEFAULT_TYPE, prompt: str):
    user_id = update.effective_user.id
    lang = user_lang(user_id)
    u = db.get_user(user_id) or {}
    model = u.get("image_model") or Config.DEFAULT_IMAGE_MODEL
    size = u.get("image_size") or "1024x1024"

    await send_upload_photo(context, update.effective_chat.id)
    status = await update.message.reply_text(t(lang, "generating_image"))
    start = time.time()
    try:
        url = await ai.generate_image(prompt, model=model, size=size)
        await status.delete()
        await update.message.reply_photo(photo=url, caption=f"🎨 {prompt[:180]}\n`{model}` · {size}",
                                         parse_mode="Markdown")
        db.log_usage(user_id, "image", model, prompt, "ok", int((time.time() - start) * 1000))
    except Exception as e:
        logger.error("image error: %s", e, exc_info=True)
        db.log_usage(user_id, "image", model, prompt, "error", int((time.time() - start) * 1000))
        await status.edit_text(t(lang, "error_api", detail=str(e)[:200]))


async def do_image_with_photo(update: Update, context: ContextTypes.DEFAULT_TYPE, prompt: str, file_id: str):
    """Image-to-image / describe: download photo, send to vision-capable text model."""
    user_id = update.effective_user.id
    lang = user_lang(user_id)
    u = db.get_user(user_id) or {}
    model = u.get("text_model") or Config.DEFAULT_TEXT_MODEL
    status = await update.message.reply_text(t(lang, "photo_processing"))
    start = time.time()
    try:
        file = await context.bot.get_file(file_id)
        photo_url = file.file_path
        resp = await ai.chat(
            messages=[
                {"role": "user", "content": [
                    {"type": "text", "text": prompt or "Describe this image in detail."},
                    {"type": "image_url", "image_url": {"url": photo_url}},
                ]},
            ],
            model=model, max_tokens=512,
        )
        await status.edit_text(f"🔍 {resp[:4000]}")
        db.log_usage(user_id, "vision", model, prompt, "ok", int((time.time() - start) * 1000))
    except Exception as e:
        logger.error("vision error: %s", e, exc_info=True)
        await status.edit_text(t(lang, "error_api", detail=str(e)[:200]))


@protected
async def cmd_image(update: Update, context: ContextTypes.DEFAULT_TYPE):
    prompt = " ".join(context.args or []).strip()
    if not prompt:
        await update.message.reply_text(t(user_lang(update.effective_user.id), "empty_prompt"))
        return
    await do_image(update, context, prompt)


def register(app):
    app.add_handler(CommandHandler("image", cmd_image))
