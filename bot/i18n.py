"""Bilingual strings (English / Arabic) with RTL support."""

STRINGS = {
    "en": {
        "welcome": (
            "✨ *Welcome to Nexus AI*\n\n"
            "Your all-in-one AI assistant — Text, Image, Audio & Video.\n\n"
            "🌐 Language: English | /lang to switch\n"
            "⚙️ /settings — customize models & defaults\n"
            "📊 /stats — usage statistics (admin)\n\n"
            "💬 Just type a message to chat, or use commands:\n"
            "🎨 /image <prompt> — generate image\n"
            "🔊 /audio <text> — text to speech\n"
            "🎬 /video <prompt> — generate video\n"
            "🧠 /models — list available AI models"
        ),
        "menu_title": "🤖 *Nexus AI — Main Menu*\nChoose a mode:",
        "btn_chat": "💬 Chat with AI",
        "btn_image": "🎨 Generate Image",
        "btn_audio": "🔊 Text to Speech",
        "btn_video": "🎬 Generate Video",
        "btn_settings": "⚙️ Settings",
        "btn_back": "⬅️ Back",
        "thinking": "🧠 Thinking…",
        "generating_image": "🎨 Generating image…",
        "generating_audio": "🔊 Generating audio…",
        "generating_video": "🎬 Generating video… (may take 1–3 min)",
        "rate_limited": "⏳ Please wait a few seconds before another request.",
        "not_allowed": "🔒 You are not authorized to use this bot.",
        "image_prompt_hint": "Send me a prompt and I'll paint it 🎨\n_Example: cyberpunk city at night, neon rain_",
        "audio_prompt_hint": "Send me text and I'll speak it 🔊\n_Example: Hello, welcome to Nexus AI_",
        "video_prompt_hint": "Send me a prompt and I'll direct it 🎬\n_Example: butterfly flying in slow motion_",
        "chat_mode_on": "💬 Chat mode active — send any message to talk.\n/menu to switch mode.",
        "image_mode_on": "🎨 Image mode active — send prompts to generate images.\n/menu to switch mode.",
        "audio_mode_on": "🔊 TTS mode active — send text to convert to speech.\n/menu to switch mode.",
        "video_mode_on": "🎬 Video mode active — send prompts to generate videos.\n/menu to switch mode.",
        "settings_title": "⚙️ *Settings*\nCurrent preferences:",
        "set_language": "🌐 Language",
        "set_text_model": "🧠 Text Model",
        "set_image_model": "🎨 Image Model",
        "set_image_size": "📐 Image Size",
        "set_voice": "🗣️ TTS Voice",
        "choose_language": "🌐 Choose your language:",
        "lang_saved": "✅ Language updated.",
        "model_saved": "✅ Model updated.",
        "size_saved": "✅ Image size updated.",
        "voice_saved": "✅ Voice updated.",
        "choose_text_model": "🧠 Choose text model:",
        "choose_image_model": "🎨 Choose image model:",
        "choose_image_size": "📐 Choose image aspect ratio:",
        "choose_voice": "🗣️ Choose a voice:",
        "processing_voice": "🎙️ Transcribing voice message…",
        "voice_reply_prefix": "🎙️ *You said:* _{transcript}_\n\n",
        "photo_received": "🖼️ Photo received! Reply with a caption / prompt to edit or describe it.",
        "photo_processing": "🔍 Analyzing your image…",
        "error_generic": "❌ Something went wrong. Please try again.",
        "error_api": "❌ AI service error: {detail}\n💡 Text/Audio/Video need a free key: https://enter.pollinations.ai/keys — add it to POLLINATIONS_API_KEY in .env",
        "empty_prompt": "📝 Please provide a prompt. Example: /image a sunset over mountains",
        "models_title": "📋 *Available AI Models* (first 40):",
        "models_fetching": "🔄 Fetching model list…",
        "stats_title": "📊 *Bot Statistics*",
        "broadcast_prompt": "📢 Send the message you want to broadcast to all users (or /cancel):",
        "broadcast_sent": "✅ Broadcast sent to {count} users.",
        "broadcast_cancelled": "❌ Broadcast cancelled.",
        "admin_only": "🔒 Admin only command.",
        "history_cleared": "🧹 Conversation history cleared.",
        "current_mode": "Current mode: {mode}",
        "select_option": "Select an option below 👇",
    },
    "ar": {
        "welcome": (
            "✨ *مرحباً بك في نيكسوس أي*\n\n"
            "مساعدك الذكي الشامل — نص، صورة، صوت وفيديو.\n\n"
            "🌐 اللغة: العربية | /lang للتغيير\n"
            "⚙️ /settings — تخصيص النماذج والإعدادات\n\n"
            "💬 اكتب أي رسالة للمحادثة، أو استخدم الأوامر:\n"
            "🎨 /image <وصف> — توليد صورة\n"
            "🔊 /audio <نص> — تحويل نص إلى كلام\n"
            "🎬 /video <وصف> — توليد فيديو\n"
            "🧠 /models — قائمة النماذج المتاحة"
        ),
        "menu_title": "🤖 *نيكسوس أي — القائمة الرئيسية*\nاختر وضعاً:",
        "btn_chat": "💬 محادثة مع الذكاء الاصطناعي",
        "btn_image": "🎨 توليد صورة",
        "btn_audio": "🔊 تحويل نص إلى كلام",
        "btn_video": "🎬 توليد فيديو",
        "btn_settings": "⚙️ الإعدادات",
        "btn_back": "⬅️ رجوع",
        "thinking": "🧠 جارٍ التفكير…",
        "generating_image": "🎨 جارٍ توليد الصورة…",
        "generating_audio": "🔊 جارٍ توليد الصوت…",
        "generating_video": "🎬 جارٍ توليد الفيديو… (قد يستغرق 1–3 دقائق)",
        "rate_limited": "⏳ يرجى الانتظار بضع ثوانٍ قبل الطلب التالي.",
        "not_allowed": "🔒 غير مصرح لك باستخدام هذا البوت.",
        "image_prompt_hint": "أرسل لي وصفاً وسأرسمه لك 🎨",
        "audio_prompt_hint": "أرسل لي نصاً وسأقولته لك 🔊",
        "video_prompt_hint": "أرسل لي وصفاً وسأخرجه فيديو 🎬",
        "chat_mode_on": "💬 وضع المحادثة مفعّل — أرسل أي رسالة.\n/menu للتبديل.",
        "image_mode_on": "🎨 وضع الصور مفعّل — أرسل أوصافاً.\n/menu للتبديل.",
        "audio_mode_on": "🔊 وضع الكلام مفعّل — أرسل نصوصاً.\n/menu للتبديل.",
        "video_mode_on": "🎬 وضع الفيديو مفعّل — أرسل أوصافاً.\n/menu للتبديل.",
        "settings_title": "⚙️ *الإعدادات*\nتفضيلاتك الحالية:",
        "set_language": "🌐 اللغة",
        "set_text_model": "🧠 نموذج النص",
        "set_image_model": "🎨 نموذج الصور",
        "set_image_size": "📐 حجم الصورة",
        "set_voice": "🗣️ صوت القارئ",
        "choose_language": "🌐 اختر لغتك:",
        "lang_saved": "✅ تم تحديث اللغة.",
        "model_saved": "✅ تم تحديث النموذج.",
        "size_saved": "✅ تم تحديث الحجم.",
        "voice_saved": "✅ تم تحديث الصوت.",
        "choose_text_model": "🧠 اختر نموذج النص:",
        "choose_image_model": "🎨 اختر نموذج الصور:",
        "choose_image_size": "📐 اختر نسبة الأبعاد:",
        "choose_voice": "🗣️ اختر الصوت:",
        "processing_voice": "🎙️ جارٍ تحويل الصوت إلى نص…",
        "voice_reply_prefix": "🎙️ *قلت:* _{transcript}_\n\n",
        "photo_received": "🖼️ تم استلام الصورة! أرسل وصفاً لتعديلها أو وصفها.",
        "photo_processing": "🔍 جارٍ تحليل الصورة…",
        "error_generic": "❌ حدث خطأ. يرجى المحاولة مرة أخرى.",
        "error_api": "❌ خطأ في خدمة الذكاء الاصطناعي: {detail}\n💡 النص/الصوت/الفيديو يتطلبون مفتاحاً مجانياً: https://enter.pollinations.ai/keys",
        "empty_prompt": "📝 يرجى إدخال وصف. مثال: /image غروب الشمس فوق الجبال",
        "models_title": "📋 *النماذج المتاحة* (أول 40):",
        "models_fetching": "🔄 جارٍ جلب قائمة النماذج…",
        "stats_title": "📊 *إحصائيات البوت*",
        "broadcast_prompt": "📢 أرسل الرسالة التي تريد بثها لجميع المستخدمين (أو /cancel):",
        "broadcast_sent": "✅ تم البث إلى {count} مستخدم.",
        "broadcast_cancelled": "❌ تم إلغاء البث.",
        "admin_only": "🔒 أمر مخصص للمشرفين فقط.",
        "history_cleared": "🧹 تم مسح سجل المحادثة.",
        "current_mode": "الوضع الحالي: {mode}",
        "select_option": "اختر خياراً أدناه 👇",
    },
}

LANG_NAMES = {"en": "🇬🇧 English", "ar": "🇸🇦 العربية"}


def t(lang: str, key: str, **kwargs) -> str:
    """Translate a string key for the given language."""
    lang = lang if lang in STRINGS else "en"
    s = STRINGS[lang].get(key, STRINGS["en"].get(key, key))
    if kwargs:
        try:
            return s.format(**kwargs)
        except (KeyError, IndexError):
            return s
    return s
