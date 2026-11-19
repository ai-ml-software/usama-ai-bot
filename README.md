# USAMA AI BOT — Production Recovery and Acceptance

Scope: `ai-ml-software/usama-ai-bot` -> Hugging Face Space `kosama685/usama-ai-bot`.
Validated against the GitHub source and uploaded errors on 18 November 2026. This archive DOES NOT contain secrets and has NOT been deployed.

## Critical P0: stop duplicate Telegram polling

1. Stop all locally running Python Telegram workers (Windows Task Manager or PowerShell `Get-CimInstance Win32_Process | Where-Object { $_.CommandLine -match 'bot.main' } | Select-Object ProcessId,CommandLine`), plus any other servers using the token. Do NOT terminate unrelated Python processes. The HF Space must be the ONLY active long-poll worker.
2. Revoke the previously exposed token via @BotFather. In Hugging Face Space settings, set the replacement as secret `TELEGRAM_BOT_TOKEN`. Remove/replace it in other platforms. NEVER paste its value into chat/logs/commits. This is required even if a credential still works.
3. Apply the patch below. It uses `fcntl` on Linux; the Windows machine only runs source checks and pushes. Don't run the Telegram worker locally at the same time as HF.
4. In Hugging Face Settings, restart the Space ONCE after changing the secret; ensure only one HF Space or other instance owns the new token.

## Apply on Windows in the cloned repo root

Unzip this archive and copy `apply_stability_fix.py` and `test_stability_patch.py` into repo root. With PowerShell opened at your repository:

```powershell
python apply_stability_fix.py --check
python apply_stability_fix.py
python -m unittest -v test_stability_patch.py
python -m unittest -v test_free_media.py
python -m compileall -q app.py bot scripts

git diff --check
git diff -- app.py bot/main.py bot/handlers/video.py
git add app.py bot/main.py bot/handlers/video.py
git diff --cached --check
git commit -m "Handle Telegram 409 conflicts and clarify verified-free video availability"
git push origin main
```

**Do not commit** any `.bak` file, `.env`, logs, bot tokens, generated artifacts, or database. Check `git status` before pushing.

## Deployment acceptance

- Check GitHub Actions deploy job has `success` **for the NEW commit SHA**, not merely an older green run.
- Check the Space repository has new `bot/main.py`, `app.py`, `bot/handlers/video.py` and `USAMA_CONFLICT_FIX_20261118` marker, and the Space is actually running.
- Confirm no repeated `telegram.error.Conflict` in logs. A renewed conflict (409) is a competing poller, not a fixable model alias. The patch stops local restart-spam, but cannot stop a remote duplicate.
- In Telegram check `/start`; send an ordinary chat; check `/image a tree`; check `/video waves on a beach`. Image/video test success depends on actual provider access and model pricing. A clear no-verified-free-video response is the correct no-billing behavior.
- Confirm the **new** Telegram token does not appear in logs. The HTTP clients are set to WARNING, but review other logging/error paths before concluding complete leak prevention.

## Media caveat

`bot/free_media.py` chooses only zero-price models from catalog metadata, and currently rejects anonymous/`pk_` keys. Changing `seedance` to a different alias or using a free credit balance is **not** evidence that video is zero-cost. The fallback is intentionally fail-closed. Never enable paid models, automatic paid retries, or a credits-consuming HF inference path without owner approval.

## Hosting caveat

A single-instance lock only coordinates processes on the same Linux filesystem. Multiple containers or other hosts require deployment coordination or one webhook endpoint. Gradio Space process-liveness is not a substitute for delivery health. Free compute/idle behavior may interrupt long polling.

## Rollback

Before deployment capture the current Git commit SHA (`git rev-parse HEAD`). If errors increase after deploy, revert the new commit (`git revert <new_commit_sha>`) and push, or restore an explicitly reviewed backup. Never restore a revoked secret.

## Incident and audit reports

Record commit SHA, Actions URL, HF Space revision and status, log window in UTC, /start result, image result, video result, 409 count, and provider billing verification. Do not mark any requirement 100% complete until the evidence is present.
