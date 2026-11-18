"""Gradio status UI and supervised Telegram poller for Hugging Face Spaces.

This supervisor does not prevent free Spaces from sleeping. Never expose secrets
in the health interface or HTTP request logs.
"""
import logging
import os
import subprocess
import sys
import threading
import time

import gradio as gr

try:
    import spaces
except ImportError:
    spaces = None

logging.basicConfig(level=logging.INFO)
logging.getLogger('httpx').setLevel(logging.WARNING)
logging.getLogger('httpcore').setLevel(logging.WARNING)
log = logging.getLogger('usama-ai-space')

_lock = threading.Lock()
_proc = None
_error = ''
_restarts = 0
_last_start = 0.0
_last_exit = None


def _spawn():
    global _proc, _error, _last_start
    if not os.getenv('TELEGRAM_BOT_TOKEN'):
        _error = 'Missing TELEGRAM_BOT_TOKEN Space secret'
        log.error(_error)
        return False
    if not os.getenv('POLLINATIONS_API_KEY'):
        log.warning('Missing POLLINATIONS_API_KEY; AI functions will be unavailable')
    try:
        _proc = subprocess.Popen(
            [sys.executable, '-m', 'bot.main'],
            cwd=os.path.dirname(os.path.abspath(__file__)),
            stdin=subprocess.DEVNULL,
            close_fds=True,
        )
        _error = ''
        _last_start = time.monotonic()
        log.info('Telegram worker spawned pid=%s', _proc.pid)
        return True
    except OSError as exc:
        _error = f'Failed to launch Telegram worker: {type(exc).__name__}'
        log.exception('Telegram worker launch error')
        _proc = None
        return False


def supervise():
    """Restart exited worker with bounded exponential backoff."""
    global _restarts, _last_exit
    delay = 5
    with _lock:
        _spawn()
    while True:
        time.sleep(5)
        with _lock:
            if _proc is None:
                continue  # absent token / spawn failure: needs operator intervention
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


def health():
    """Process liveness, not Telegram delivery or AI provider readiness."""
    with _lock:
        alive = _proc is not None and _proc.poll() is None
        return {
            'status': 'worker_alive' if alive else 'worker_down',
            'telegram_token_configured': bool(os.getenv('TELEGRAM_BOT_TOKEN')),
            'ai_key_configured': bool(os.getenv('POLLINATIONS_API_KEY')),
            'restart_count': _restarts,
            'last_exit_code': _last_exit,
            'error': _error or None,
            'note': 'Worker liveness does not verify message delivery or continuous hosting.',
        }


if spaces is not None:
    @spaces.GPU(duration=1)
    def _zerogpu_compatibility_probe():
        return 'compatibility probe'

threading.Thread(target=supervise, daemon=True, name='telegram-supervisor').start()

with gr.Blocks(title='Usama AI Bot — Operations') as demo:
    gr.Markdown('# Usama AI Bot — Operations\nProcess-level status only. Never display credentials.')
    result = gr.JSON(value=health(), label='Runtime health')
    gr.Button('Refresh health').click(fn=health, inputs=[], outputs=result, api_name='health')
    if spaces is not None:
        hidden = gr.Textbox(visible=False)
        gr.Button('Compatibility', visible=False).click(
            fn=_zerogpu_compatibility_probe, inputs=[], outputs=hidden)
    gr.Markdown('Free Spaces may sleep. Deploy to always-on infrastructure for 24/7 polling.')

if __name__ == '__main__':
    demo.launch(server_name='0.0.0.0', server_port=int(os.getenv('PORT', '7860')), ssr_mode=False)
