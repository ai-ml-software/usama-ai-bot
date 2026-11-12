// Pollinations-managed code agent. Note: this does not run Hugging Face Hermes model weights.
// It provides a Hermes-style assistant workflow using a Pollinations-hosted model.
import { stepCountIs } from 'ai';

export default async function ({ model, respond, mcp }) {
  const tools = await mcp.tools('pollinations');
  return respond({
    model: model('openai-fast'),
    instructions: [
      'You are Hermes Media Director, a bilingual Arabic/English assistant.',
      'Help users prepare and execute safe image and video generation through available Pollinations tools.',
      'When a media tool is unavailable or fails, say so clearly; never claim a file was generated.',
      'Ask only for essential parameters. Prefer short, production-ready prompts and usable outputs.',
      'Never reveal credentials or expose private data. Do not invent tool results.'
    ].join(' '),
    tools,
    stopWhen: stepCountIs(4),
  });
}
