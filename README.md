# Hermes + Pollinations deployment kit

## Important architecture distinction
Hermes Agent (the local agent runtime) can be configured to use Pollinations as a model provider. That **does not** deploy NousResearch/Hermes-4-14B weights from Hugging Face to Pollinations. A hosted HF Hermes model requires a separately provisioned inference endpoint and a compatible provider/model-registration flow.

## Option A — Connect an installed Hermes Agent directly to Pollinations (recommended)
1. Install Hermes Agent using its official installer for your operating system: https://hermes-agent.nousresearch.com/
2. In a trusted terminal on the machine running Hermes, execute:

```bash
npx @pollinations/cli@latest harness hermes on
polli harness hermes status
polli mcp install hermes --all
```

The first command signs in interactively when needed, configures `$HERMES_HOME/config.yaml`, stores a dedicated key in `$HERMES_HOME/.env`, and installs the Polli skill. The model can be changed with `hermes model`; available IDs must be selected from the **live Pollinations** catalog. Test both image and video tool availability; these operations may require account balance/permissions.

## Option B — Publish this managed code agent (a text/tool-using agent, not the Hermes weights)
1. Create a NEW PUBLIC GitHub repository exclusively for the agent, e.g. `hermes-pollinations-agent`, and put `agent.ts` at its root.
2. Replace `YOUR_GITHUB_USERNAME/YOUR_PUBLIC_AGENT_REPO` in `code-agent.json` with the actual repository.
3. Visit https://enter.pollinations.ai/my-models, connect the intended Pollinations account, and add a **Code agent**, pasting the repository URL. Keep **private** visibility until tested.
4. Alternatively, when authorized in the Pollinations CLI, run `npx @pollinations/cli@latest agents create --config code-agent.json`.
5. Check the returned agent ID/name and test a response through `/v1/responses` with an account key. Check available media tools independently; managed code agents are text-only as an outer API.
6. To auto-sync, set the GitHub repository variable `POLLINATIONS_SYNC_URL` to `https://gen.pollinations.ai/account/agents/ACTUAL_AGENT_ID/sync` (replace the ID). Do not insert an API key in the repository.

## Hugging Face reference
Recommended optional HF model: https://huggingface.co/NousResearch/Hermes-4-14B
Do not claim it is being used unless an authorized, deployed inference endpoint has been created and tested for this specific model. Hugging Face model repository visibility does not imply hosted inference availability.

## Security and deployment verification
- Never store API keys or Telegram tokens in GitHub files or chats.
- Keep Pollinations agent visibility private by default; public visibility requires community publisher privileges.
- Test an actual response, image generation and video generation; verify video MP4 upload to Telegram separately.
- The code-agent repository and agent identity are fixed at creation; use a dedicated public repo.
- For Telegram bot operation, a separate Telegram webhook/worker deployment is necessary; simply publishing this agent will not connect Telegram.
