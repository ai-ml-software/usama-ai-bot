#!/usr/bin/env python3
"""Apply a minimal conflict-safe repair to the existing Usama AI Bot repository.

Run from repository root. No token values are read or written by this script.
Idempotent and fails if expected source anchors have changed.
"""
from __future__ import annotations
import argparse
import ast
from pathlib import Path
import re
import shutil

ROOT = Path.cwd()
MARK = '# USAMA_CONFLICT_FIX_20261118'

def patch_main(src: str) -> str:
    if MARK in src:
        return src
    # Consolidate repeated log suppressions from earlier patch runs.
    src = re.sub(r"(?m)^    # Telegram (?:HTTP URLs embed bot tokens\. Never log these URLs\.|embeds the secret in its API URL: never log httpx request URLs\.)\n", '', src)
    src = re.sub(r"(?m)^    logging\.getLogger\('(httpx|httpcore)'\)\.setLevel\(logging\.WARNING\)\n", '', src)
    anchor = '    logging.basicConfig(level=logging.INFO, format=fmt, handlers=handlers)'
    if src.count(anchor) != 1:
        raise RuntimeError('bot/main.py logging anchor differs from reviewed repository')
    src = src.replace(anchor, anchor + "\n    # Suppress Telegram token-bearing URLs in HTTP client logs.\n    logging.getLogger('httpx').setLevel(logging.WARNING)\n    logging.getLogger('httpcore').setLevel(logging.WARNING)\n", 1)
    anchor = '    from telegram.ext import Application'
    if src.count(anchor) != 1:
        raise RuntimeError('bot/main.py application anchor missing')
    src = src.replace(anchor, '''    # USAMA_CONFLICT_FIX_20261118
    # Advisory lock is host-local. It does NOT coordinate separate servers.
    import fcntl
    from telegram.error import Conflict
    lock_file = open(os.path.join(Config.DATA_DIR, 'telegram_poller.lock'), 'a+b')
    try:
        fcntl.flock(lock_file.fileno(), fcntl.LOCK_EX | fcntl.LOCK_NB)
    except BlockingIOError:
        logger.error('Another local bot poller already holds the lock; refusing to start.')
        raise SystemExit(78)
    conflict_seen = {'value': False}

    async def on_error(update, context):
        error = context.error
        if isinstance(error, Conflict):
            if not conflict_seen['value']:
                logger.critical('Telegram polling conflict (409): another process uses this token. Stop duplicate pollers or rotate the token.')
                conflict_seen['value'] = True
                context.application.stop_running()
            return
        # Avoid rendering potentially secret-bearing exception details in logs.
        logger.error('Telegram handler failed: %s', type(error).__name__)

''' + anchor, 1)
    anchor = '    app = Application.builder().token(Config.BOT_TOKEN).build()'
    if src.count(anchor) != 1:
        raise RuntimeError('bot/main.py Application build anchor missing')
    src = src.replace(anchor, anchor + '\n    app.add_error_handler(on_error)', 1)
    needle = '    app.run_polling(drop_pending_updates=False, allowed_updates=["message", "callback_query"])'
    if src.count(needle) != 1:
        raise RuntimeError('bot/main.py polling anchor missing; check latest code')
    src = src.replace(needle, '''    try:
        app.run_polling(
            drop_pending_updates=False,
            allowed_updates=['message', 'callback_query'],
            bootstrap_retries=0,
        )
    finally:
        fcntl.flock(lock_file.fileno(), fcntl.LOCK_UN)
        lock_file.close()
    if conflict_seen['value']:
        raise SystemExit(78)  # Supervisor must NOT restart a 409 conflict.
''', 1)
    return src


def patch_app(src: str) -> str:
    if MARK in src:
        return src
    needle = "            _last_exit = code\n            log.error('Telegram worker exited: %s; restarting after %ss', code, delay)"
    if src.count(needle) != 1:
        raise RuntimeError('app.py supervisor anchor differs from reviewed source')
    src = src.replace(needle, '''            _last_exit = code
            # USAMA_CONFLICT_FIX_20261118
            if code == 78:
                global _proc, _error
                _proc = None
                _error = ('Telegram conflict or duplicate local poller. '
                          'Stopped automatic restarts until the duplicate is resolved. '
                          'Rotate leaked token and restart this Space.')
                log.critical(_error)
                return
            if _restarts >= 10:
                _proc = None
                _error = 'Too many Telegram worker crashes; restarts halted pending operator action.'
                log.critical(_error)
                return
            log.error('Telegram worker exited (code %s); restarting after %ss', code, delay)''', 1)
    # global statement must occur before any use of _proc in the function, not inside conditional.
    src = src.replace('    global _restarts, _last_exit', '    global _restarts, _last_exit, _proc, _error', 1)
    src = src.replace('                global _proc, _error\n', '', 1)
    return src


def patch_video(src: str) -> str:
    if MARK in src:
        return src
    # Only if deployed code is using the new fail-closed generation layer.
    needle = "from ..free_media import generate_media"
    if needle not in src:
        raise RuntimeError('bot/handlers/video.py is not the auto-free version')
    src = src.replace(needle, needle + ', NoVerifiedFreeModel\n# USAMA_CONFLICT_FIX_20261118', 1)
    needle = '    except Exception as e:'
    if src.count(needle) < 1:
        raise RuntimeError('bot/handlers/video.py exception anchor missing')
    src = src.replace(needle, '''    except NoVerifiedFreeModel:
        db.log_usage(user_id, 'video', model, prompt, 'no_free_model', int((time.time() - start) * 1000))
        await status.edit_text(
            'No verified free video model is available on the configured provider right now. '
            'No paid model was used and no generation charge was requested. '
            'Try again when a verified free model is published.'
        )
    except Exception as e:''', 1)
    return src


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--check', action='store_true')
    args = parser.parse_args()
    targets = {'bot/main.py':patch_main, 'app.py':patch_app, 'bot/handlers/video.py':patch_video}
    outputs = {}
    for path, patch in targets.items():
        dest = ROOT/path
        if not dest.exists():
            parser.error(f'Missing {path}; run from repository root')
        before = dest.read_text(encoding='utf-8')
        after = patch(before)
        ast.parse(after, filename=path)
        outputs[path] = (before, after)
    if args.check:
        print('PASS: reviewed anchors found; all three generated Python files parse; no changes written')
        return
    for path,(before,after) in outputs.items():
        if before == after:
            print('UNCHANGED',path)
            continue
        dest = ROOT/path
        backup = dest.with_name(dest.name+'.pre-conflict-fix.bak')
        if not backup.exists():
            shutil.copy2(dest,backup)
        dest.write_text(after,encoding='utf-8')
        print('UPDATED',path)
    print('Local patch installed. Review diff; run tests; commit/push; rotate token; test on one active poller.')

if __name__ == '__main__':
    main()
