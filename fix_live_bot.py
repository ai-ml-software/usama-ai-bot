#!/usr/bin/env python3
"""Integrate the existing free_media.py in the actual Telegram bot handlers.
Run once from the root of ai-ml-software/usama-ai-bot.
Does not call any paid generation endpoint during installation.
"""
from pathlib import Path
import ast
import shutil
import sys

root=Path.cwd()
if not (root/'bot/main.py').is_file() or not (root/'free_media.py').is_file():
    raise SystemExit('ERROR: Run from repository root; bot/main.py and free_media.py are required.')

changes={}
def replace_once(path, old, new):
    s=changes.get(path, (root/path).read_text(encoding='utf8'))
    if old in s:
        s=s.replace(old,new,1)
    elif new not in s:
        raise RuntimeError(f'{path}: expected code not found; do not overwrite unknown changes')
    changes[path]=s

# Install model-selector module inside bot package so its relative Config import works.
mod=(root/'free_media.py').read_text(encoding='utf8')
# Fail closed on conflicting provider metadata: free=True must not override a positive price.
mod=mod.replace("    if model.get('free') is True or model.get('is_free') is True:\n        return True\n", "")
mod=mod.replace("    if model.get('free') is False or model.get('is_free') is False:\n        return False\n", "    if model.get('free') is False or model.get('is_free') is False:\n        return False\n")
# When explicit model.free is true and there is no price metadata, not enough evidence to guarantee zero bill.
changes['bot/free_media.py']=mod

p='bot/handlers/video.py'
replace_once(p,'from ..ai_client import ai, VIDEO_MODELS','from ..free_media import generate_media')
replace_once(p,'    model = VIDEO_MODELS[0]',"    model = 'auto-free'")
replace_once(p, '''        url = await ai.generate_video(prompt, model=model)
        await status.delete()
        try:
            await update.message.reply_video(video=url, caption=f"🎥 {prompt[:180]}\\n`{model}`",
                                             parse_mode="Markdown", supports_streaming=True)
        except Exception:
            # Fallback: send as document if video upload fails
            await update.message.reply_document(document=url, caption=f"🎥 {prompt[:180]}")''', '''        video_file, model = await generate_media('video', prompt, duration=4)
        await update.message.reply_video(video=video_file, caption=f"🎥 {prompt[:180]}\\n{model}", supports_streaming=True)
        await status.delete()''')

p='bot/handlers/image.py'
replace_once(p,'from ..ai_client import ai','from ..ai_client import ai\nfrom ..free_media import generate_media')
replace_once(p,'''        url = await ai.generate_image(prompt, model=model, size=size)
        await status.delete()
        await update.message.reply_photo(photo=url, caption=f"🎨 {prompt[:180]}\\n`{model}` · {size}",
                                         parse_mode="Markdown")''','''        try:
            width, height = (int(v) for v in size.split('x'))
        except (ValueError, AttributeError):
            width, height = 1024, 1024
        image_file, model = await generate_media('image', prompt, width=width, height=height)
        await update.message.reply_photo(photo=image_file, caption=f"🎨 {prompt[:180]}\\n{model} · {size}")
        await status.delete()''')

p='bot/main.py'
replace_once(p,'    logging.basicConfig(level=logging.INFO, format=fmt, handlers=handlers)', '''    logging.basicConfig(level=logging.INFO, format=fmt, handlers=handlers)
    # Telegram HTTP URLs embed bot tokens. Never log these URLs.
    logging.getLogger('httpx').setLevel(logging.WARNING)
    logging.getLogger('httpcore').setLevel(logging.WARNING)''')
replace_once(p,'drop_pending_updates=True','drop_pending_updates=False')

p='bot/keyboards.py'
replace_once(p,'for i, m in enumerate(IMAGE_MODELS):','for i, m in enumerate(["auto-free"]):')

for path,source in changes.items():
    ast.parse(source,filename=path)
if '--check' in sys.argv:
    print('PASS: 5 target files parsed; no changes written')
    raise SystemExit(0)
for path,source in changes.items():
    dst=root/path
    if dst.exists() and dst.read_text(encoding='utf8')==source:
        continue
    if dst.exists():
        backup=dst.with_name(dst.name+'.before-auto-free.bak')
        if not backup.exists(): shutil.copy2(dst, backup)
    dst.write_text(source,encoding='utf8')
    print('UPDATED',path)
print('Integration complete locally. Next: run tests, commit, deploy; rotate exposed Telegram token.')
