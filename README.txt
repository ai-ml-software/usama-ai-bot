USAMA AI BOT - FIX INVALID SEEDANCE MODEL ROUTE

Security first: your Telegram token was exposed in runtime logs. Revoke it using @BotFather, create a new one, and update Hugging Face Space Secrets. Do not put it into repository files or ChatGPT.

1. Download fix_live_bot.py to your local clone of https://github.com/ai-ml-software/usama-ai-bot
2. Open terminal in repo root.
3. Run:
   python fix_live_bot.py --check
   python fix_live_bot.py
   python -m compileall -q app.py bot scripts
   python -m unittest test_free_media.py -v
   git status --short
   git diff -- bot
4. Review changes. Add only desired code files (not backup files):
   git add bot/free_media.py bot/handlers/video.py bot/handlers/image.py bot/keyboards.py bot/main.py
   git commit -m "Activate zero-cost media routing and protect Telegram logs"
   git push origin main
5. Verify GitHub Actions deploy is green and Hugging Face Space /bot/handlers/video.py imports generate_media.
6. Test /start, /image cat, /video cat from Telegram, and check logs.

IMPORTANT: Video generation succeeds only if Pollinations offers an explicitly verified zero-cost video model and the provided server API key has access. The software will not silently charge for paid media. If no such model exists, a clear 'No verified zero-cost video model' message is expected. Existing free_media.py's catalog/parser must be matched to current provider API response before relying on production. The patch does not guarantee free video availability.

GitHub connector previously returned HTTP 403. Manual Git push with a credential having repository Contents write access is necessary unless the connector permissions change.
