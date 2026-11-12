# Usama Telegram Free Bot

An isolated Cloudflare Worker that defaults to zero-spend mode. Free `/health` and `/models` endpoints work without any key. Telegram replies work after the owner configures `TELEGRAM_BOT_TOKEN` and `TELEGRAM_WEBHOOK_SECRET` in Cloudflare Worker secrets and registers `/telegram/webhook` with Telegram. Media commands intentionally never execute paid/credit-consuming API requests until a genuinely free provider is separately validated.

## Security

Never commit bot tokens or API keys. Set the secrets in Cloudflare dashboard. Optionally set `ALLOWED_USER_IDS` to comma-separated numeric Telegram user IDs. Register the webhook using the Bot API `setWebhook` method with the worker HTTPS URL and the same secret token. Do not include credentials in the webhook URL itself.

## Cost policy

No calls to paid models; no billing credentials; no upgrades or auto-purchases. Cloudflare usage stays within the account's own plan allowance. Pollinations currently requires an `sk_` secret for generation, and the existence of public models does not guarantee free inference.
