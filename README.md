# Telegram Media Director — Pollinations + Cloudflare Worker

An independent deployable integration: `/image` and `/video` in Telegram; Pollinations-powered generation; asynchronous Cloudflare Queue; private Pollinations code agent (`agent.ts`) that prepares prompts. These are **two separate services**: Pollinations managed agents return text; the Telegram Worker calls media endpoints and uploads actual image/video files.

## Requirements
- A Telegram bot token from BotFather and a Pollinations API key with adequate credits and model access.
- A Cloudflare account with Workers and Queues enabled. Queues and generation can incur charges.
- For the optional managed **code agent**: public GitHub repo with `agent.ts` at its root, linked Pollinations/GitHub account, and `account:keys` permission. Public agent visibility requires community publisher access; default private is recommended.

## Deploy the Telegram Worker
1. Install Node 20+ and Wrangler (`npm install -g wrangler`), then `wrangler login`.
2. Create a queue: `wrangler queues create telegram-media-generation`.
3. Store secrets (never commit or paste values into chats):
   - `wrangler secret put TELEGRAM_BOT_TOKEN`
   - `wrangler secret put TELEGRAM_WEBHOOK_SECRET` (random 32+ alphanumeric, hyphen or underscore chars)
   - `wrangler secret put POLLINATIONS_API_KEY`
4. Optionally restrict users: `wrangler secret put ALLOWED_USER_IDS` (comma-separated Telegram numeric user IDs).
5. Verify the selected image and video models support `/image` and `/video` in the **live** Pollinations catalog. Update `IMAGE_MODEL`, `VIDEO_MODEL` and `VIDEO_DURATION` in `wrangler.toml` if necessary.
6. `wrangler deploy` and note your HTTPS Workers URL.
7. Register the webhook locally with your own bot token and webhook secret (never paste credentials into third-party sites):
   ```sh
   curl -sS "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/setWebhook" \
     -d "url=https://YOUR-WORKER.workers.dev/telegram/webhook" \
     -d "secret_token=${TELEGRAM_WEBHOOK_SECRET}" \
     -d 'allowed_updates=["message"]'
   ```
8. Test `/start`, `/image red desert sunset`, `/video waves under moonlight`; inspect `wrangler tail` and queue delivery health. Check `https://YOUR-WORKER.workers.dev/health` (does not reveal secrets).

## Optional Pollinations managed code agent
Create a **new public GitHub repo** with the root `agent.ts` and this repository README; replace the placeholder repository URL in `code-agent.json`. Then in your authenticated terminal:
```sh
npx @pollinations/cli agents create --config code-agent.json
```
Or create it using the Pollinations dashboard at https://enter.pollinations.ai/my-models . Enter the public repository URL, keep **agent visibility private** and require privacy/secrets/sexual/violence/shield features. The account needs GitHub linkage and `account:keys` to manage agents. After creation, set the GitHub repository variable `POLLINATIONS_SYNC_URL` to the exact `https://gen.pollinations.ai/account/agents/AGENT_ID/sync` value. The included GitHub Action syncs changes made to `agent.ts` on main.

## Troubleshooting matrix
- **401/403 from Pollinations**: API key, model permission, or depleted balance. Do not disclose the key.
- **404/model unavailable**: Retrieve current model catalog and update model names. Video support must be checked separately from image support.
- **402/429**: Insufficient funds/quota or rate limit. Apply backoff, avoid continuous retry loops.
- **Telegram upload fails**: Confirm returned content type is image/video, file below Bot API upload limits, and bot can message the chat.
- **Webhook 403**: Verify `X-Telegram-Bot-Api-Secret-Token` matches the secret registered with Telegram.
- **Webhook replies but no media**: Check Cloudflare Queue consumer, Pollinations credentials/model, Worker logs, and outbound Telegram API errors.

## Operational notes
- Telegram webhook returns quickly; a Queue consumer generates media after acknowledgement, avoiding webhook timeout. Queue redelivery can cause duplicate sends after a successful upload but before acknowledgement; add a durable idempotency store before high-scale launch.
- Do not expose API keys in source code, worker responses, logs, GitHub commits, or Telegram chat.
- Image content is uploaded using sendPhoto; some generators produce formats or dimensions Telegram rejects. Convert media or use sendDocument if supported in your environment.
- Video generation can be costly or slower than photos. Model availability and quota depend on the provider.
- `agent.ts` does not connect to your Telegram bot by itself. The Cloudflare Worker handles actual Telegram integration.
