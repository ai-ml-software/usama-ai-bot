export function parseCommand(text) {
  if (typeof text !== 'string') return { kind: 'ignore' };
  const input = text.trim();
  const match = input.match(/^\/(start|help|image|video)(?:@[a-zA-Z0-9_]+)?(?:\s+([\s\S]*))?$/i);
  if (!match) return { kind: 'ignore' };
  const command = match[1].toLowerCase();
  if (command === 'start' || command === 'help') return { kind: 'help' };
  const prompt = (match[2] || '').trim();
  if (!prompt) return { kind: 'missing', media: command };
  if (prompt.length > 1600) return { kind: 'too_long' };
  return { kind: 'generate', media: command, prompt };
}
export function mediaUrl(base, media, prompt, model, duration) {
  const endpoint = new URL(`${base.replace(/\/$/, '')}/${media}/${encodeURIComponent(prompt)}`);
  endpoint.searchParams.set('model', model);
  if (media === 'video') endpoint.searchParams.set('duration', String(duration));
  return endpoint.toString();
}
