import { parseCommand, mediaUrl } from './commands.mjs';

// Cloudflare Queue decouples the fast Telegram webhook from slow image/video generation.
const HELP = 'Welcome to Media Director!\n\n/image a futuristic hotel lobby at sunset\n/video cinematic drone shot over desert dunes\n\nGeneration may take time. Videos require a supported model and available provider credits.';
const MAX_MEDIA_BYTES = 48 * 1024 * 1024; // Below Telegram's common 50 MB upload limit

function required(env, ...names) {
  for (const name of names) if (!env[name]) throw new Error(`Missing Worker secret/binding: ${name}`);
}
function isAllowed(message, env) {
  if (!env.ALLOWED_USER_IDS) return true;
  const allowed = env.ALLOWED_USER_IDS.split(',').map(x => x.trim());
  return allowed.includes(String(message.from?.id));
}
async function tg(env, method, payload) {
  const response = await fetch(`https://api.telegram.org/bot${env.TELEGRAM_BOT_TOKEN}/${method}`, {
    method: 'POST', body: JSON.stringify(payload), headers: {'Content-Type': 'application/json'}
  });
  const result = await response.json().catch(() => ({}));
  if (!response.ok || result.ok === false) throw new Error(`Telegram ${method}: ${response.status} ${String(result.description || '').slice(0, 250)}`);
  return result;
}
async function tell(env, chat_id, text) {
  return tg(env, 'sendMessage', {chat_id, text: text.slice(0, 4000)});
}
async function runGeneration(job, env) {
  const model = job.media === 'image' ? (env.IMAGE_MODEL || 'flux') : (env.VIDEO_MODEL || 'google/veo-3.1-fast');
  const duration = Number(env.VIDEO_DURATION || '4');
  if (!Number.isInteger(duration) || duration < 1 || duration > 8) throw new Error('VIDEO_DURATION must be an integer between 1 and 8');
  const url = mediaUrl(env.POLLINATIONS_BASE_URL || 'https://gen.pollinations.ai', job.media, job.prompt, model, duration);
  const response = await fetch(url, {headers: { Authorization: `Bearer ${env.POLLINATIONS_API_KEY}` }, signal: AbortSignal.timeout(job.media === 'video' ? 540000 : 180000)});
  if (!response.ok) {
    const body = await response.text().catch(() => '');
    throw new Error(`Pollinations ${response.status}: ${body.slice(0, 240)}`);
  }
  const contentType = (response.headers.get('content-type') || '').toLowerCase();
  const expected = job.media === 'video' ? 'video/' : 'image/';
  if (!contentType.startsWith(expected) && !contentType.startsWith('application/octet-stream')) {
    throw new Error(`Unexpected media response: ${contentType}`);
  }
  const size = Number(response.headers.get('content-length') || 0);
  if (size > MAX_MEDIA_BYTES) throw new Error('Media exceeds Telegram size limit');
  const blob = await response.blob();
  if (blob.size > MAX_MEDIA_BYTES || blob.size === 0) throw new Error('Generated media is empty or too large');
  const filename = job.media === 'video' ? 'generated-video.mp4' : 'generated-image.png';
  const form = new FormData();
  form.set('chat_id', String(job.chat_id));
  form.set('caption', `Created with Media Director • ${model}`.slice(0, 1024));
  form.set(job.media === 'video' ? 'video' : 'photo', blob, filename);
  const method = job.media === 'video' ? 'sendVideo' : 'sendPhoto';
  const result = await fetch(`https://api.telegram.org/bot${env.TELEGRAM_BOT_TOKEN}/${method}`, { method: 'POST', body: form });
  const json = await result.json().catch(() => ({}));
  if (!result.ok || !json.ok) throw new Error(`Telegram upload ${result.status}: ${String(json.description || '').slice(0, 250)}`);
}
export default {
  async fetch(request, env) {
    if (request.method === 'GET' && new URL(request.url).pathname === '/health') {
      return Response.json({ ok: true, service: 'telegram-media-bot', image: !!env.IMAGE_MODEL, video: !!env.VIDEO_MODEL });
    }
    if (request.method !== 'POST' || new URL(request.url).pathname !== '/telegram/webhook') return new Response('Not Found', {status: 404});
    try {
      required(env, 'TELEGRAM_BOT_TOKEN', 'TELEGRAM_WEBHOOK_SECRET', 'POLLINATIONS_API_KEY', 'MEDIA_QUEUE');
      if (request.headers.get('X-Telegram-Bot-Api-Secret-Token') !== env.TELEGRAM_WEBHOOK_SECRET) return new Response('Forbidden', {status: 403});
      const update = await request.json();
      const message = update.message;
      if (!message || !isAllowed(message, env)) return new Response('OK');
      const cmd = parseCommand(message.text);
      if (cmd.kind === 'ignore') return new Response('OK');
      if (cmd.kind === 'help') { await tell(env, message.chat.id, HELP); return new Response('OK'); }
      if (cmd.kind === 'missing') { await tell(env, message.chat.id, `Please send /${cmd.media} followed by a description.`); return new Response('OK'); }
      if (cmd.kind === 'too_long') { await tell(env, message.chat.id, 'Please shorten your prompt (maximum 1600 characters).'); return new Response('OK'); }
      await env.MEDIA_QUEUE.send({ chat_id: message.chat.id, update_id: update.update_id, media: cmd.media, prompt: cmd.prompt });
      await tell(env, message.chat.id, `Your ${cmd.media} request was queued. I will send the result here when ready.`);
      return new Response('OK');
    } catch (error) {
      // Non-2xx response tells Telegram to retry after transient webhook failures.
      console.error('webhook error', String(error));
      return new Response('Temporary error', {status: 503});
    }
  },
  async queue(batch, env) {
    required(env, 'TELEGRAM_BOT_TOKEN', 'POLLINATIONS_API_KEY');
    for (const message of batch.messages) {
      try {
        await runGeneration(message.body, env);
        message.ack();
      } catch (error) {
        console.error('media generation failed', String(error));
        if (message.attempts < 3) message.retry({delaySeconds: 30 * message.attempts});
        else {
          await tell(env, message.body.chat_id, 'Media generation failed after retries. Check the image/video model, available credits, and provider status. Please try again later.').catch(console.error);
          message.ack();
        }
      }
    }
  }
};
