"""Inline keyboard builders for the bot's menu-driven UI."""
from telegram import InlineKeyboardButton, InlineKeyboardMarkup

from .i18n import t, LANG_NAMES
from .ai_client import TEXT_MODELS, IMAGE_MODELS, IMAGE_SIZES, TTS_VOICES


def _btn(label: str, data: str) -> InlineKeyboardButton:
    return InlineKeyboardButton(label, callback_data=data)


def main_menu(lang: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup([
        [_btn(t(lang, "btn_chat"), "mode:chat"), _btn(t(lang, "btn_image"), "mode:image")],
        [_btn(t(lang, "btn_audio"), "mode:audio"), _btn(t(lang, "btn_video"), "mode:video")],
        [_btn(t(lang, "btn_settings"), "settings:main")],
    ])


def settings_menu(lang: str) -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup([
        [_btn(t(lang, "set_language"), "settings:lang"),
         _btn(t(lang, "set_text_model"), "settings:text_model")],
        [_btn(t(lang, "set_image_model"), "settings:image_model"),
         _btn(t(lang, "set_image_size"), "settings:image_size")],
        [_btn(t(lang, "set_voice"), "settings:voice")],
        [_btn(t(lang, "btn_back"), "menu:main")],
    ])


def language_menu() -> InlineKeyboardMarkup:
    return InlineKeyboardMarkup([
        [_btn(LANG_NAMES["en"], "set:lang:en"), _btn(LANG_NAMES["ar"], "set:lang:ar")],
        [_btn("⬅️ Back", "settings:main")],
    ])


def text_model_menu() -> InlineKeyboardMarkup:
    rows = []
    row = []
    for i, m in enumerate(TEXT_MODELS):
        row.append(_btn(m, f"set:text_model:{m}"))
        if len(row) == 2:
            rows.append(row)
            row = []
    if row:
        rows.append(row)
    rows.append([_btn("⬅️ Back", "settings:main")])
    return InlineKeyboardMarkup(rows)


def image_model_menu() -> InlineKeyboardMarkup:
    rows = []
    row = []
    for i, m in enumerate(IMAGE_MODELS):
        row.append(_btn(m, f"set:image_model:{m}"))
        if len(row) == 2:
            rows.append(row)
            row = []
    if row:
        rows.append(row)
    rows.append([_btn("⬅️ Back", "settings:main")])
    return InlineKeyboardMarkup(rows)


def image_size_menu() -> InlineKeyboardMarkup:
    rows = []
    for label, size in IMAGE_SIZES.items():
        rows.append([_btn(label, f"set:image_size:{size}")])
    rows.append([_btn("⬅️ Back", "settings:main")])
    return InlineKeyboardMarkup(rows)


def voice_menu() -> InlineKeyboardMarkup:
    rows = []
    row = []
    for i, v in enumerate(TTS_VOICES):
        row.append(_btn(v, f"set:tts_voice:{v}"))
        if len(row) == 3:
            rows.append(row)
            row = []
    if row:
        rows.append(row)
    rows.append([_btn("⬅️ Back", "settings:main")])
    return InlineKeyboardMarkup(rows)
