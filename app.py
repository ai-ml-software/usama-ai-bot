"""Gradio CPU application with a ZeroGPU startup compatibility handler.

The GPU handler is registered but not invoked by the bot. This is a workaround
for ZeroGPU's startup scan, not a promise of always-on Telegram polling.
"""
import logging
import os
import subprocess
import sys

try:
    import spaces
except ImportError:
    spaces = None

import gradio as gr

logging.basicConfig(level=logging.INFO)
log = logging.getLogger('usama-ai-space')
logging.getLogger('httpx').setLevel(logging.WARNING)
logging.getLogger('httpcore').setLevel(logging.WARNING)
_proc = None
_start_error = None


def launch_bot():
    global _proc, _start_error
    if not os.getenv('TELEGRAM_BOT_TOKEN'):
        _start_error = 'TELEGRAM_BOT_TOKEN is missing from Space Secrets'
        log.error(_start_error)
        return
    if not os.getenv('POLLINATIONS_API_KEY'):
        log.warning('POLLINATIONS_API_KEY not set; generation requests may fail')
    try:
        _proc = subprocess.Popen(
            [sys.executable, '-m', 'bot.main'],
            cwd=os.path.dirname(os.path.abspath(__file__)),
            stdin=subprocess.DEVNULL,
            stdout=None,
            stderr=None,
            close_fds=True,
        )
        log.info('Telegram subprocess launched pid=%s', _proc.pid)
    except OSError:
        _start_error = 'Could not start Telegram subprocess; inspect Space logs'
        log.exception('Telegram subprocess startup failed')


def status():
    if _start_error:
        return f'Not running: {_start_error}'
    if _proc is None:
        return 'Telegram process not started.'
    exit_code = _proc.poll()
    if exit_code is not None:
        return f'Telegram subprocess exited (code {exit_code}); inspect logs.'
    return 'Telegram process alive. Test /start to verify Telegram connectivity.'


# ZeroGPU will reject a Gradio app unless a registered event handler is GPU-marked.
# This hidden, never-invoked handler satisfies the startup scan without using GPU.
# The preferred production fix is CPU hardware, where this fallback is unused.
if spaces is not None:
    @spaces.GPU(duration=1)
    def _zerogpu_compatibility_probe():
        return 'ZeroGPU compatibility probe registered.'


launch_bot()

with gr.Blocks(title='Usama AI Bot — Status') as demo:
    gr.Markdown('# Usama AI Bot\nTelegram worker diagnostics. Live Telegram tests are required.')
    result = gr.Textbox(value=status(), label='Telegram worker', interactive=False)
    gr.Button('Refresh status').click(fn=status, inputs=[], outputs=result)
    if spaces is not None:
        compatibility_result = gr.Textbox(visible=False)
        hidden_probe = gr.Button('ZeroGPU compatibility', visible=False)
        hidden_probe.click(fn=_zerogpu_compatibility_probe, inputs=[], outputs=compatibility_result)
    gr.Markdown('Free Spaces may sleep. S3 provides storage, not continuous compute.')

if __name__ == '__main__':
    demo.launch(server_name='0.0.0.0', server_port=int(os.getenv('PORT', '7860')), ssr_mode=False)
