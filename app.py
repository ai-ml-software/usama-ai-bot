"""Gradio status frontend and supervised Telegram subprocess.

This is a CPU Basic compatible compromise, not an always-on hosting guarantee.
Telegram polling runs in its own main thread in a separate Python process,
avoiding asyncio signal-handler errors caused by launching run_polling in a thread.
"""
import logging
import os
import subprocess
import sys
import time

import gradio as gr

logging.basicConfig(level=logging.INFO)
log = logging.getLogger("usama-ai-space")
_proc = None
_start_error = None


def launch_bot():
    global _proc, _start_error
    if not os.getenv("TELEGRAM_BOT_TOKEN"):
        _start_error = "Missing TELEGRAM_BOT_TOKEN secret"
        log.error(_start_error)
        return
    if not os.getenv("POLLINATIONS_API_KEY"):
        log.warning("POLLINATIONS_API_KEY is not set: provider requests will fail")
    try:
        _proc = subprocess.Popen(
            [sys.executable, "-m", "bot.main"],
            stdin=subprocess.DEVNULL,
            stdout=None,
            stderr=None,
            close_fds=True,
        )
        log.info("Telegram polling subprocess started with pid=%s", _proc.pid)
    except (OSError, ValueError) as exc:
        _start_error = type(exc).__name__
        log.exception("Cannot start Telegram subprocess")


def status():
    if _start_error:
        return f"Not running: {_start_error}"
    if _proc is None:
        return "Not started: configure TELEGRAM_BOT_TOKEN"
    exit_code = _proc.poll()
    if exit_code is not None:
        return f"Telegram process exited with code {exit_code}. Inspect Space logs."
    return "Telegram process is running. This does not prove Telegram or AI API connectivity; test /start."


launch_bot()

with gr.Blocks(title="Usama AI Bot") as demo:
    gr.Markdown("# Usama AI Bot\nDeployment process status. Not a live Telegram service check.")
    result = gr.Textbox(label="Telegram worker", value=status(), interactive=False)
    gr.Button("Refresh status").click(fn=status, outputs=result)
    gr.Markdown("The Space may sleep on free CPU Basic hardware. S3 is storage, not compute.")

if __name__ == "__main__":
    demo.launch(server_name="0.0.0.0", server_port=int(os.getenv("PORT", "7860")))
