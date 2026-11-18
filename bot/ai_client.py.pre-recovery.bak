"""Pollinations AI client with retry, fallback, and streaming support.

Image generation uses the free direct endpoint (image.pollinations.ai) which
requires no API key. Text / audio / video use the OpenAI-compatible endpoint
(gen.pollinations.ai) which requires a free API key from
https://enter.pollinations.ai/keys — anonymous access was retired.
"""
import asyncio
import logging
import random
import urllib.parse
from typing import AsyncIterator, Optional

from openai import AsyncOpenAI, APIError, RateLimitError, APIConnectionError

from .config import Config

logger = logging.getLogger(__name__)

FREE_IMAGE_BASE = "https://image.pollinations.ai/prompt/"

# Known-good model presets (verified against Pollinations catalog)
TEXT_MODELS = [
    "gpt-5.4-mini", "openai", "gemini-2.5-flash", "gemini-3-flash",
    "deepseek-v4-flash", "deepseek-chat", "llama3.3", "mistral",
    "qwen2.5-72b", "gpt-4o-mini",
]
IMAGE_MODELS = ["flux", "flux-realism", "flux-anime", "turbo", "kontext", "gpt-image", "midjourney"]
IMAGE_SIZES = {
    "Square (1:1)": "1024x1024",
    "Portrait (9:16)": "720x1280",
    "Landscape (16:9)": "1280x720",
    "Wide (21:9)": "1536x640",
    "Photo (4:3)": "1152x896",
}
TTS_VOICES = ["Rachel", "Domi", "Bella", "Antoni", "Elli", "Josh", "Arnold", "Adam", "Sam"]
VIDEO_MODELS = ["seedance", "wan-fast", "wan2.1", "kling"]


class AIClient:
    def __init__(self):
        self.client = AsyncOpenAI(base_url=Config.AI_BASE_URL, api_key=Config.AI_API_KEY)
        self._models_cache: Optional[list[str]] = None

    async def list_models(self, refresh: bool = False) -> list[str]:
        if self._models_cache and not refresh:
            return self._models_cache
        try:
            resp = await self.client.models.list()
            self._models_cache = sorted(m.id for m in resp.data)
            return self._models_cache
        except Exception as e:
            logger.warning("Failed to fetch models: %s", e)
            return TEXT_MODELS + IMAGE_MODELS + VIDEO_MODELS

    async def _retry(self, coro_factory, attempts: int = 3):
        """Retry with exponential backoff on transient errors."""
        for attempt in range(attempts):
            try:
                return await coro_factory()
            except (RateLimitError, APIConnectionError) as e:
                if attempt == attempts - 1:
                    raise
                wait = 2 ** attempt
                logger.warning("API transient error (attempt %d): %s — retrying in %ds", attempt + 1, e, wait)
                await asyncio.sleep(wait)
            except APIError as e:
                if attempt == attempts - 1 or e.status_code not in (500, 502, 503, 504):
                    raise
                wait = 2 ** attempt
                logger.warning("API %s (attempt %d) — retrying in %ds", e.status_code, attempt + 1, wait)
                await asyncio.sleep(wait)

    # ─── Text ───────────────────────────────────────────────
    async def chat(self, messages: list[dict], model: str, max_tokens: int = 1024,
                   temperature: float = 0.7) -> str:
        async def _call():
            resp = await self.client.chat.completions.create(
                model=model, messages=messages, max_tokens=max_tokens,
                temperature=temperature,
            )
            return resp.choices[0].message.content or ""
        return await self._retry(_call)

    async def chat_stream(self, messages: list[dict], model: str,
                          max_tokens: int = 1024) -> AsyncIterator[str]:
        stream = await self.client.chat.completions.create(
            model=model, messages=messages, max_tokens=max_tokens, stream=True,
        )
        async for chunk in stream:
            if chunk.choices and chunk.choices[0].delta.content:
                yield chunk.choices[0].delta.content

    # ─── Image (free direct endpoint, no key needed) ────────
    def _free_image_url(self, prompt: str, size: str, model: str) -> str:
        w, h = (size.split("x") + ["1024", "1024"])[:2]
        seed = random.randint(1, 2_000_000_000)
        enc = urllib.parse.quote(prompt)
        return (f"{FREE_IMAGE_BASE}{enc}?width={w}&height={h}"
                f"&model={urllib.parse.quote(model)}&nologo=true&seed={seed}")

    async def generate_image(self, prompt: str, model: str, size: str = "1024x1024") -> str:
        # Primary: free direct endpoint (always works, no API key)
        url = self._free_image_url(prompt, size, model)
        # If user has an API key, prefer the higher-quality OpenAI endpoint
        if Config.AI_API_KEY and Config.AI_API_KEY != "anonymous":
            try:
                async def _call():
                    resp = await self.client.images.generate(model=model, prompt=prompt, size=size, n=1)
                    return resp.data[0].url
                return await self._retry(_call)
            except Exception as e:
                logger.warning("OpenAI image endpoint failed (%s), falling back to free URL", e)
        return url

    # ─── Audio TTS ──────────────────────────────────────────
    async def generate_speech(self, text: str, voice: str, out_path: str):
        async def _call():
            resp = await self.client.audio.speech.create(
                model="elevenlabs", voice=voice, input=text,
            )
            resp.stream_to_file(out_path)
            return out_path
        return await self._retry(_call)

    # ─── Video ──────────────────────────────────────────────
    async def generate_video(self, prompt: str, model: str = "seedance") -> str:
        async def _call():
            resp = await self.client.images.generate(
                model=model, prompt=prompt, size="1280x720", n=1,
            )
            return resp.data[0].url
        return await self._retry(_call)


ai = AIClient()
