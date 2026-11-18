#!/usr/bin/env python3
"""Apply fail-closed zero-cost media selection to the existing Usama bot checkout."""
from pathlib import Path
import ast
import shutil
import sys

root=Path.cwd()
source=Path(__file__).with_name('free_media.py')
if not (root/'bot/handlers/image.py').exists():
    raise SystemExit('Run in the GitHub bot repository root.')

def replace(p,old,new):
    s=(root/p).read_text(encoding='utf-8')
    if old not in s:
        if new in s: return s
        raise RuntimeError(f'{p}: anchor missing; inspect code version before applying')
    return s.replace(old,new,1)

changes={'bot/free_media.py':source.read_text(encoding='utf-8')}
p='bot/handlers/image.py'
s=replace(p,'from ..ai_client import ai','from ..ai_client import ai\nfrom ..free_media import generate_media')
s=s.replace('        url = await ai.generate_image(prompt, model=model, size=size)\n        await status.delete()\n        await update.message.reply_photo(photo=url, caption=f"🎨 {prompt[:180]}\\n`{model}` · {size}",\n                                         parse_mode="Markdown")',
'''        try:
            width, height = [int(v) for v in size.split('x')]
        except (ValueError, AttributeError):
            width, height = 1024, 1024
        media, model = await generate_media('image', prompt, width=width, height=height)
        await update.message.reply_photo(photo=media, caption=f"🎨 {prompt[:180]}\\n{model} · {size}")
        await status.delete()''')
if 'media, model = await generate_media' not in s:raise RuntimeError('image handler source mismatch')
changes[p]=s
p='bot/handlers/video.py'
s=replace(p,'from ..ai_client import ai, VIDEO_MODELS','from ..free_media import generate_media')
s=replace_video='''    model = VIDEO_MODELS[0]'''
s=s.replace(replace_video,'''    model = 'auto-free' ''')
start='''        url = await ai.generate_video(prompt, model=model)
        await status.delete()
        try:
            await update.message.reply_video(video=url, caption=f"🎥 {prompt[:180]}\\n`{model}`",
                                             parse_mode="Markdown", supports_streaming=True)
        except Exception:
            # Fallback: send as document if video upload fails
            await update.message.reply_document(document=url, caption=f"🎥 {prompt[:180]}")'''
repl='''        video, model = await generate_media('video', prompt, duration=4)
        await update.message.reply_video(video=video, caption=f"🎥 {prompt[:180]}\\n{model}", supports_streaming=True)
        await status.delete()'''
if start not in s:raise RuntimeError('video handler source mismatch')
s=s.replace(start,repl)
changes[p]=s
# Avoid presenting paid/hardcoded image models that the automatic handler ignores.
p='bot/keyboards.py'
s=replace(p,'for i, m in enumerate(IMAGE_MODELS):', 'for i, m in enumerate(["auto-free"]):')
changes[p]=s
# Security: the Telegram API URL in HTTP request logs embeds the active bot token.
p='bot/main.py'
s=replace(p,'    logging.basicConfig(level=logging.INFO, format=fmt, handlers=handlers)',
'''    logging.basicConfig(level=logging.INFO, format=fmt, handlers=handlers)
    logging.getLogger('httpx').setLevel(logging.WARNING)
    logging.getLogger('httpcore').setLevel(logging.WARNING)''')
changes[p]=s
for p,s in changes.items():ast.parse(s,filename=p)
if '--check' in sys.argv:
    print('PASS: all source anchors matched and generated Python parsed successfully')
    raise SystemExit(0)
for p,s in changes.items():
    dst=root/p
    if dst.exists() and dst.read_text(encoding='utf-8')==s: continue
    if dst.exists():shutil.copy2(dst,str(dst)+'.auto-free.bak')
    dst.write_text(s,encoding='utf-8')
    print('Updated',p)
print('Patch applied. Run tests before deploying. Never enable a paid fallback.')
