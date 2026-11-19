import ast
import importlib.util
from pathlib import Path
import unittest

spec = importlib.util.spec_from_file_location('stability', Path(__file__).with_name('apply_stability_fix.py'))
s = importlib.util.module_from_spec(spec)
spec.loader.exec_module(s)

MAIN = '''import logging
import os
import sys
from logging.handlers import RotatingFileHandler
from bot.config import Config

def setup_logging():
    Config.ensure_dirs()
    fmt = 'log'
    handlers = []
    log_file = 'file'
    logging.basicConfig(level=logging.INFO, format=fmt, handlers=handlers)
    logging.getLogger('httpx').setLevel(logging.WARNING)
    logging.getLogger('httpcore').setLevel(logging.WARNING)
    # Telegram embeds the secret in its API URL: never log httpx request URLs.
    logging.getLogger('httpx').setLevel(logging.WARNING)
    logging.getLogger('httpcore').setLevel(logging.WARNING)

def main():
    setup_logging()
    logger = logging.getLogger('nexus')
    from telegram.ext import Application
    from bot.handlers import start, chat, image, audio, video, admin
    app = Application.builder().token(Config.BOT_TOKEN).build()
    app.run_polling(drop_pending_updates=False, allowed_updates=["message", "callback_query"])
'''
APP = '''import time
import logging
import threading
import os
import subprocess
import sys
_lock=threading.Lock()
_proc=None
_error=''
_restarts=0
_last_start=0.0
_last_exit=None
log=logging.getLogger('test')
def _spawn(): pass
def supervise():
    global _restarts, _last_exit
    delay = 5
    with _lock:
        _spawn()
    while True:
        time.sleep(5)
        with _lock:
            if _proc is None:
                continue
            code = _proc.poll()
            if code is None:
                if time.monotonic() - _last_start >= 300:
                    delay = 5
                continue
            _last_exit = code
            log.error('Telegram worker exited: %s; restarting after %ss', code, delay)
        time.sleep(delay)
        with _lock:
            _restarts += 1
            _spawn()
        delay = min(delay * 2, 300)
'''
VIDEO = '''from ..free_media import generate_media
import time
from ..database import db
async def do_video(update, context, prompt):
    user_id=123
    model='auto-free'
    start=time.time()
    status=await update.message.reply_text('Generating')
    try:
        media,model=await generate_media('video',prompt,duration=4)
    except Exception as e:
        pass
'''

class Tests(unittest.TestCase):
    def test_main(self):
        result=s.patch_main(MAIN)
        ast.parse(result)
        self.assertEqual(result.count("logging.getLogger('httpx')"),1)
        self.assertEqual(result.count('app.add_error_handler(on_error)'),1)
        self.assertIn('raise SystemExit(78)', result)
        self.assertEqual(result,s.patch_main(result))
    def test_supervisor(self):
        result=s.patch_app(APP)
        ast.parse(result)
        self.assertIn('if code == 78:',result)
        self.assertIn('if _restarts >= 10:',result)
        self.assertEqual(result,s.patch_app(result))
    def test_video(self):
        result=s.patch_video(VIDEO)
        ast.parse(result)
        self.assertIn('except NoVerifiedFreeModel:',result)
        self.assertEqual(result,s.patch_video(result))

if __name__=='__main__': unittest.main()
