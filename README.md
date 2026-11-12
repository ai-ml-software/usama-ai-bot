# ✨ Nexus AI — Telegram Bot

**Production-grade, all-in-one AI assistant for Telegram** — powered by Pollinations AI.
Text chat (with memory + streaming), Image generation, Text-to-Speech, Video generation,
Voice message transcription, Photo analysis, bilingual EN/AR UI, admin dashboard.

No paid API key required (anonymous per-IP rate limit; optional free key raises limits).

---

## 🚀 Quick Start (auto-configured)

```bash
# 1. Install deps + auto-setup (writes .env, sets bot commands on Telegram)
python setup.py

# 2. Run
python -m bot.main
# or on Windows:  run.bat
```

Then open Telegram → your bot → `/start`.

---

## 🎯 Features

| Feature | Details |
|---------|---------|
| 💬 **AI Chat** | Multi-turn conversation memory (SQLite), streaming replies, typing indicator |
| 🎨 **Image Gen** | `/image <prompt>` or image mode; pick model + aspect ratio in settings |
| 🔊 **Text-to-Speech** | `/audio <text>`; 9 ElevenLabs voices selectable in settings |
| 🎬 **Video Gen** | `/video <prompt>`; Seedance/Wan models, streaming delivery |
| 🎙️ **Voice Messages** | Send a voice note → auto-transcribed + AI replied |
| 🖼️ **Photo Analysis** | Send photo + caption → vision model describes/edits it |
| 🌐 **Bilingual** | English / العربية with RTL; `/lang` toggles, settings menu too |
| ⚙️ **User Settings** | Per-user: language, text model, image model, image size, TTS voice |
| 📊 **Admin Dashboard** | `/stats` usage breakdown, `/users` list, `/broadcast` to all users |
| 🔒 **Access Control** | Optional allow-list + admin list via `.env` |
| ⏱️ **Rate Limiting** | Token-bucket per user (configurable) |
| 🔄 **Retry Logic** | Exponential backoff on transient API errors |
| 📝 **Logging** | Rotating file logs + console |

---

## 📋 Commands

| Command | Description |
|---------|-------------|
| `/start` | Welcome + main menu (inline buttons) |
| `/menu` | Open main menu |
| `/image <prompt>` | Generate image |
| `/audio <text>` | Text to speech |
| `/video <prompt>` | Generate video |
| `/models` | List available AI models (live) |
| `/lang` | Toggle EN/AR |
| `/settings` | Customize models & defaults |
| `/clear` | Clear chat history |
| `/stats` | Usage statistics *(admin)* |
| `/broadcast` | Broadcast message to all users *(admin)* |
| `/help` | Show help |

---

## 🏗️ Architecture

```
bot/
├── main.py            # Entry point, logging, handler registration
├── config.py          # Environment config + paths
├── database.py        # SQLite: users, conversations, usage logs
├── ai_client.py       # Pollinations OpenAI client + retry + model presets
├── i18n.py            # Bilingual strings (EN/AR)
├── keyboards.py       # Inline keyboard builders
├── middleware.py      # Auth, rate-limit, typing indicators, decorators
└── handlers/
    ├── start.py       # /start /help /menu /models /lang + callback router
    ├── chat.py        # Text chat (streaming, memory), voice STT, photo input
    ├── image.py       # Image generation + vision analysis
    ├── audio.py       # TTS generation
    ├── video.py       # Video generation
    └── admin.py       # /stats /users /broadcast
```

---

## ⚙️ Configuration (.env)

| Variable | Purpose |
|----------|---------|
| `TELEGRAM_BOT_TOKEN` | Bot token from @BotFather |
| `ADMIN_USER_IDS` | Comma-separated admin Telegram IDs |
| `ALLOWED_USER_IDS` | Restrict bot to these IDs (empty = public) |
| `POLLINATIONS_API_KEY` | Optional — get free key at auth.pollinations.ai |
| `RATE_LIMIT_SECONDS` | Per-user cooldown (default 5) |
| `DEFAULT_LANGUAGE` | `en` or `ar` |

---

## 📦 Deploy (24/7 on VPS)

```bash
# systemd service — /etc/systemd/system/nexus-ai.service
[Unit]
Description=Nexus AI Telegram Bot
After=network.target

[Service]
Type=simple
WorkingDirectory=/opt/nexus-ai
ExecStart=/usr/bin/python3 -m bot.main
Restart=always
RestartSec=10
EnvironmentFile=/opt/nexus-ai/.env

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now nexus-ai
```

---

Built with `python-telegram-bot` + OpenAI SDK (Pollinations-compatible).
