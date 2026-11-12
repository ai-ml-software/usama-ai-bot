# Usama AI Bot — deployment kit (12 November 2026)

## Current verified condition
- GitHub repository `ai-ml-software/usama-ai-bot`: corrected `app.py` and `bot/storage.py` already exist on main.
- Hugging Face Space `kosama685/usama-ai-bot`: still has an old, syntactically invalid `app.py` and no complete bot code.
- HF Bucket `kosama685/usama-ai-bot-storage`: exists but listing returned zero objects.
- GitHub connector write attempts return 403, so this kit is NOT committed or deployed.
- HF Sandbox test previously returned 402, so storage wasn't tested live.

## Apply via GitHub Web UI (requires repo write permission)
1. Open the GitHub repo; add `.github/workflows/deploy-huggingface-space.yml` from this kit.
2. The repo **already** has corrected `app.py`, `bot/storage.py`, and `scripts/check_storage.py`; do not duplicate or overwrite them unless comparing revisions.
3. Confirm `requirements.txt` includes `boto3` as well as Telegram, OpenAI SDK, Gradio, and dependencies of existing handlers.
4. In GitHub repository Settings > Secrets and variables > Actions, create `HF_TOKEN` with write permission to `kosama685/usama-ai-bot` (separate from HF Space secrets).
5. Commit the workflow file. GitHub Actions > Deploy Usama AI Bot to Hugging Face Gradio Space > Run workflow.
6. Confirm the workflow reports a push, and the Hugging Face Space Files tab now contains `app.py`, `bot/`, `scripts/`, `requirements.txt`, and `README.md`.
7. The Hugging Face Space retains its own secrets; use Settings > Variables and secrets to verify names: `TELEGRAM_BOT_TOKEN`, `POLLINATIONS_API_KEY`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`. Verify values yourself; these cannot be read here.
8. Set Space variables `HF_S3_BUCKET=usama-ai-bot-storage`, `HF_S3_ENDPOINT=https://s3.hf.co/kosama685`, `HF_S3_PREFIX=telegram-bot` if used.
9. Check Space Build + Container logs. The Space status page only proves the subprocess exists; it does not verify Telegram messaging or Pollinations API.
10. If using polling, remove an older Telegram webhook and stop any other poller. Then test `/start`, text, image, audio, video separately. Do not assume untested model aliases work.
11. On a host with S3 credentials, run `python -m scripts.check_storage` to write/read back a test object and verify S3. The bucket alone is not a database or compute host.

## Deploy without GitHub Actions (direct HF Space upload)
Download the complete repository locally, overlay the corrected kit files, and upload the complete app to the existing HF Space. Do not upload only `app.py`: it imports the `bot` package. Include a Space metadata `README.md` with `sdk: gradio`, `app_file: app.py`.

## Operational constraints
Free CPU Basic Gradio Spaces may sleep and do not guarantee an always-on Telegram polling process. A healthy Gradio UI and subprocess status do not prove bot delivery. For reliable 24/7, migrate the worker to always-on hosting. Secrets must never be committed. S3 is object storage, not a direct SQLite filesystem.
