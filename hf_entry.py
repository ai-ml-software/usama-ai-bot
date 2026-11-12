import os
import threading
import logging
from fastapi import FastAPI
import uvicorn

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("hf_entry")

health_app = FastAPI()

@health_app.get("/")
def health():
    return {"status": "ok", "app": "nexus-ai-bot"}

def run_telegram_bot():
    """Thread target: imports the bot and calls its main() (long-polling)."""
    from bot.main import main as bot_main
    bot_main()

if __name__ == "__main__":
    threading.Thread(target=run_telegram_bot, daemon=True).start()
    uvicorn.run(health_app, host="0.0.0.0", port=int(os.environ.get("PORT", "7860")))