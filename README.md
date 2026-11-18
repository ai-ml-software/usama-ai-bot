# Usama AI Telegram Bot — automatic free media patch

## Purpose
Automatically discover models from the Pollinations `/image/models` and `/video/models` catalogs and generate only when the catalog *explicitly verifies zero-cost pricing*. Unsupported, paid, unavailable or ambiguous models **must not be used**. This is deliberate: free account credits are not the same as zero-price generation. If no free video model is published, the bot will explain that it cannot generate free videos rather than charging credits or fabricating output.

## Install
1. Rotate the Telegram bot token through BotFather because the previous runtime logs exposed it in Telegram request URLs. Set the new token as `TELEGRAM_BOT_TOKEN` in Hugging Face Space Secrets. Do **not** paste secrets into GitHub.
2. Clone `ai-ml-software/usama-ai-bot` and run from its root:
   `python /path/to/telegram_auto_free_repair/enable_auto_free.py --check`
3. Apply patch: `python /path/to/telegram_auto_free_repair/enable_auto_free.py`
4. Set a valid secret `POLLINATIONS_API_KEY` (`sk_...`) in Hugging Face Space Secrets; the public `pk_...` identifier is insufficient.
5. Inspect diff, compile Python, test on staging and commit. Push to `main` to trigger the existing GitHub Actions deployment to `kosama685/usama-ai-bot`. Verify the deployment job and HF Space logs.
6. Test `/start`, `/image a tree`, and `/video waves on a beach` in Telegram. A no-free-model response for video means there is **no verified zero-price video provider**, not that the bot has silently failed.

## Files
- `free_media.py`: place at `bot/free_media.py`. Uses provider catalog and verifies pricing before generation, no paid fallbacks.
- `enable_auto_free.py`: applies corresponding handler and log-security changes, conservatively using exact source anchors.
- `test_free_media.py`: four offline tests for paid model blocking and authentication behavior.

## Provider
- Catalog: https://gen.pollinations.ai/video/models and https://gen.pollinations.ai/image/models
- Video endpoint: `GET /video/{prompt}` returning MP4
- Image endpoint: `GET /image/{prompt}` returning image bytes

## Limitations and cautions
- Models and prices change: selection is dynamic, cached for five minutes. A provider pricing change during cache lifetime could alter actual billing; confirm the platform-side key budget/credit cap or use a separate restricted key if strict spending control is essential.
- The model catalog response format has not been live-tested from this sandbox. The selector intentionally fails closed on unknown formats. Catalog pricing may include different fields; verify with real metadata before deployment.
- Video generation can take a long time and might exceed the Hugging Face/Telegram host time budget; always-on server or async jobs are preferred for production.
- The repair does not make Hugging Face free Spaces always-on.
- There is no verified, guaranteed unlimited free video model. A free trial balance is still a consumable credit, not guaranteed free generation.
- This is a code package, **not a completed production deployment**. The connected GitHub integration has previously rejected content updates with 403.
