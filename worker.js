// Usama Telegram Free Bot — safe-by-default, no paid model calls.
// Deploy to Cloudflare Workers, then configure Telegram secrets in dashboard.
const HEADERS = { 'content-type': 'application/json; charset=utf-8', 'cache-control': 'no-store', 'x-content-type-options': 'nosniff' };
const VERSION = '2026-11-free-v1';
function reply(data, status = 200) { return new Response(JSON.stringify(data), { status, headers: HEADERS }); }
function configured(env) { return Boolean(env.TELEGRAM_BOT_TOKEN && env.TELEGRAM_WEBHOOK_SECRET); }
async function send(env, chatId, text) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 8000);
  try {
    const r = await fetch(`https://api.telegram.org/bot${env.TELEGRAM_BOT_TOKEN}/sendMessage`, {
      method: 'POST', headers: { 'content-type': 'application/json' },
      body: JSON.stringify({ chat_id: chatId, text: text.slice(0, 4000) }), signal: controller.signal
    });
    if (!r.ok) throw new Error(`Telegram sendMessage HTTP ${r.status}`);
    const body = await r.json();
    if (!body.ok) throw new Error('Telegram sendMessage rejected');
  } finally { clearTimeout(timeout); }
}
const HELP = 'Usama AI Bot — Free Mode\n/start — Menu\n/help — Help\n/status — Services status\n/models — Pollinations public model catalog\n/image prompt — Image generation (requires verified free allowance)\n/video prompt — Video generation (requires verified free allowance)\n/audio text — Speech generation (requires verified free allowance)\n\nNo paid requests are made. Generation remains disabled until you enable a verified zero-cost provider.';
export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    if (request.method === 'GET' && url.pathname === '/health') {
      return reply({ ok: true, service: 'usama-telegram-free-bot', version: VERSION, telegram_configured: configured(env), generation_enabled: false, zero_spend: true });
    }
    if (request.method === 'GET' && url.pathname === '/models') {
      try {
        const upstream = await fetch('https://gen.pollinations.ai/v1/models', { headers: { accept: 'application/json' }, signal: AbortSignal.timeout(6000) });
        if (!upstream.ok) return reply({ ok: false, upstream_status: upstream.status }, 502);
        const payload = await upstream.json();
        const names = (payload.data || []).slice(0, 20).map(m => m.id).filter(Boolean);
        return reply({ ok: true, count_shown: names.length, models: names, note: 'Listing is free; generation is not guaranteed free.' });
      } catch { return reply({ ok: false, error: 'Model catalog temporarily unavailable' }, 503); }
    }
    if (request.method !== 'POST' || url.pathname !== '/telegram/webhook') return reply({ error: 'Not found' }, 404);
    if (!configured(env)) return reply({ error: 'Telegram configuration incomplete' }, 503);
    const header = request.headers.get('X-Telegram-Bot-Api-Secret-Token');
    if (!header || header !== env.TELEGRAM_WEBHOOK_SECRET) return reply({ error: 'Forbidden' }, 403);
    let update;
    try { update = await request.json(); } catch { return reply({ error: 'Invalid JSON' }, 400); }
    const msg = update?.message;
    if (!msg?.chat?.id || typeof msg.text !== 'string') return reply({ ok: true, ignored: true });
    if (env.ALLOWED_USER_IDS && !env.ALLOWED_USER_IDS.split(',').map(s => s.trim()).includes(String(msg.from?.id))) return reply({ ok: true, ignored: true });
    const cmd = /^\/(start|help|status|models|image|video|audio)(?:@[a-zA-Z0-9_]+)?(?:\s+([\s\S]*))?$/i.exec(msg.text.trim());
    if (!cmd) return reply({ ok: true, ignored: true });
    let text = HELP;
    switch (cmd[1].toLowerCase()) {
      case 'status': text = '✅ Bot online\n🔒 Zero-spend mode: ON\n🖼 Image: disabled until free access is verified\n🎬 Video: disabled until free access is verified\n🔊 Audio: disabled until free access is verified'; break;
      case 'models': text = 'Pollinations public catalog (no API key needed): https://gen.pollinations.ai/v1/models\nModel listing is free; generation may require key and Pollen.'; break;
      case 'image': case 'video': case 'audio': text = `Your ${cmd[1].toLowerCase()} request was NOT sent. This bot never charges money or Pollen automatically. Current Pollinations generation requires authorization and may use credits. Provide a verified free allowance before enabling this command.`; break;
    }
    try { await send(env, msg.chat.id, text); return reply({ ok: true }); }
    catch (error) { console.error('Telegram message delivery failure', String(error)); return reply({ error: 'Temporary Telegram delivery error' }, 503); }
  }
};
