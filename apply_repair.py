#!/usr/bin/env python3
"""Conservative, repeatable patch for ai-ml-software/usama-ai-bot.
Run in the repository root: python path/to/apply_repair.py
Never run with or print a real token. Review diff before pushing.
"""
from pathlib import Path
import argparse
import ast
import shutil
import sys

ROOT = Path.cwd()
HERE = Path(__file__).resolve().parent

def replace_once(text, old, new, filename):
    if old not in text:
        if new in text:
            return text
        raise RuntimeError(f'{filename}: source anchor missing, review upstream changes')
    return text.replace(old, new, 1)

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--check', action='store_true', help='validate changes without writing')
    args = ap.parse_args()
    if not (ROOT / 'bot/main.py').exists():
        raise SystemExit('Run from the bot repository root.')
    new_files = {}
    new_files['app.py'] = (HERE / 'app.py').read_text(encoding='utf8')
    p = 'bot/main.py'
    s = (ROOT / p).read_text(encoding='utf8')
    s = replace_once(s, '    logging.basicConfig(level=logging.INFO, format=fmt, handlers=handlers)',
      "    logging.basicConfig(level=logging.INFO, format=fmt, handlers=handlers)\n"
      "    # Telegram embeds the secret in its API URL: never log httpx request URLs.\n"
      "    logging.getLogger('httpx').setLevel(logging.WARNING)\n"
      "    logging.getLogger('httpcore').setLevel(logging.WARNING)", p)
    s = replace_once(s, '    app.run_polling(drop_pending_updates=True, allowed_updates=["message", "callback_query"])',
      '    # Preserve queued updates across restarts so incoming messages are not discarded.\n'
      '    app.run_polling(drop_pending_updates=False, allowed_updates=["message", "callback_query"])', p)
    new_files[p] = s
    p = 'bot/config.py'
    s = (ROOT / p).read_text(encoding='utf8')
    s = replace_once(s, '    DEFAULT_IMAGE_MODEL: str = os.getenv("DEFAULT_IMAGE_MODEL", "turbo")',
      '    DEFAULT_IMAGE_MODEL: str = os.getenv("DEFAULT_IMAGE_MODEL", "flux")', p)
    new_files[p] = s
    p = 'bot/ai_client.py'
    s = (ROOT / p).read_text(encoding='utf8')
    # Do not silently claim an unsupported video endpoint works. The current
    # implementation was calling images.generate for a video model.
    old = '''    async def generate_video(self, prompt: str, model: str = "seedance") -> str:
        async def _call():
            resp = await self.client.images.generate(
                model=model, prompt=prompt, size="1280x720", n=1,
            )
            return resp.data[0].url
        return await self._retry(_call)'''
    new = '''    async def generate_video(self, prompt: str, model: str = "seedance") -> str:
        raise RuntimeError(
            "Video generation is temporarily unavailable: the previous implementation "
            "used the image API with an invalid video alias. Configure a supported "
            "Pollinations video model and video endpoint before enabling this feature."
        )'''
    s = replace_once(s, old, new, p)
    # Old hardcoded image model names cannot be assumed supported; selection
    # is still shown but API validation happens at request time.
    new_files[p] = s
    for path, content in new_files.items():
        ast.parse(content, filename=path)
    if args.check:
        print('PASS: all four target files parse; source anchors verified; no writes')
        return
    for path, content in new_files.items():
        target = ROOT / path
        if target.exists() and target.read_text(encoding='utf8') == content:
            continue
        if target.exists():
            backup = target.with_suffix(target.suffix + '.pre-recovery.bak')
            if not backup.exists():
                shutil.copy2(target, backup)
        target.write_text(content, encoding='utf8')
        print(f'Patched {path}')
    print('PASS: code parses. Next: run tests, rotate leaked token, review git diff, deploy, run live checks.')

if __name__ == '__main__':
    main()
