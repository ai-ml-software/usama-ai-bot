# Usama AI Bot: deployment repair + HF S3 storage integration

## Confirmed state (12 November 2026)

- GitHub: `ai-ml-software/usama-ai-bot` exists. Connected GitHub app returns HTTP 403 when creating an integration branch. No live code was committed.
- Space: `kosama685/usama-ai-bot` exists as a Gradio Space, but its existing app.py is syntactically invalid and was **not updated**.
- Bucket: `kosama685/usama-ai-bot-storage` exists, but listing returned no objects. Hugging Face Sandbox creation with bucket mount returned **402 Payment Required**. No S3 live test was run.
- This package is a candidate patch, NOT an end-to-end deployed bot.

## Apply to existing GitHub repository (do not replace the entire project)

1. Replace root `app.py` with this package's `app.py`. This launches the existing Telegram polling app in an independent process instead of a background thread (important for python-telegram-bot event loop and signal handling).
2. Add `bot/storage.py` and `scripts/check_storage.py`; add an empty `scripts/__init__.py`.
3. Merge the entry in `requirements-extra.txt` into the existing `requirements.txt` (`boto3>=1.35,<2`) while keeping existing dependencies.
4. Set HF Space Secrets: `TELEGRAM_BOT_TOKEN`, `POLLINATIONS_API_KEY`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`.
5. Set HF Space Variables: `HF_S3_BUCKET=usama-ai-bot-storage`, `HF_S3_ENDPOINT=https://s3.hf.co/kosama685`, `HF_S3_PREFIX=telegram-bot`, and optional `PORT=7860`.
6. Configure an S3 credential with least privilege for the bucket through Hugging Face Access Tokens. Do not publish credentials in GitHub, chats, or logs.
7. Deploy **the complete existing project plus these changes** to `kosama685/usama-ai-bot`. Updating GitHub alone does NOT update the separate HF Space.
8. Verify Space logs and bot subprocess, then send `/start` via Telegram. Ensure that no Telegram webhook is set while using polling (`getWebhookInfo` and `deleteWebhook` as needed). Do not simultaneously run a second polling instance.
9. Run `python -m scripts.check_storage` inside the host environment once credentials exist. The check writes `telegram-bot/s3-healthcheck.txt`, downloads it, and compares the contents.
10. Test all existing commands (`/text`, `/image`, `/audio`, `/video`) with genuine provider API access. The current codebase has unverified model IDs and Pollinations interfaces; **do not claim these modes work until tested**.

## Important architecture constraints

- S3 is object storage. This adapter does not yet automatically save the bot's image, voice, or video output; wire `upload_file` into individual handlers only after agreeing on retention and access controls.
- **Do not put active SQLite database files on S3 or directly mount S3 as a SQLite working directory.** Use a persistent relational database for durable state or export stopped/consistent backup snapshots to S3.
- Free Gradio Spaces can sleep and cannot guarantee 24/7 Telegram polling. For reliable 24/7 bot operation use a persistent worker host or webhook-aware server with uptime guarantees.
- Do not expose private media objects via public URLs.
- Always validate spending limits, retries and media sizes before accepting public users.
