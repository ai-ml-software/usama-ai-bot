"""Zero-charge dynamic Pollinations model selection. No hardcoded paid fallbacks."""
import asyncio
import io
import logging
import time
from urllib.parse import quote

import httpx

from .config import Config

log = logging.getLogger(__name__)
ROOT = 'https://gen.pollinations.ai'
CACHE_SECONDS = 300
_CACHE = {}

class NoVerifiedFreeModel(RuntimeError):
    pass


def _price_is_explicitly_zero(model):
    """Fail closed on missing/ambiguous pricing (credits != no charge)."""
    if model.get('free') is True or model.get('is_free') is True:
        return True
    if model.get('free') is False or model.get('is_free') is False:
        return False
    pricing = model.get('pricing', model.get('price'))
    if isinstance(pricing, (float, int)) and not isinstance(pricing, bool):
        return pricing == 0
    if not isinstance(pricing, dict) or not pricing:
        return False
    vals=[]
    def scan(d):
        for k,v in d.items():
            key=k.lower()
            if isinstance(v, dict): scan(v)
            elif isinstance(v,(int,float)) and not isinstance(v,bool) and any(s in key for s in ('price','cost','credit','pollen','input','output','second','image','video','usd','token')):
                vals.append(float(v))
            elif isinstance(v,str) and any(s in key for s in ('price','cost')):
                try: vals.append(float(v))
                except ValueError: pass
    scan(pricing)
    return bool(vals) and all(v == 0 for v in vals)


def _candidates(catalog, kind):
    if isinstance(catalog, dict):
        catalog = catalog.get('data', catalog.get('models', []))
    if not isinstance(catalog,list):
        raise NoVerifiedFreeModel('Unexpected provider model catalog format; no paid fallback attempted.')
    candidates=[]
    for entry in catalog:
        if not isinstance(entry,dict) or not _price_is_explicitly_zero(entry):
            continue
        ident=entry.get('id') or entry.get('name')
        if not isinstance(ident,str) or not ident:
            continue
        if entry.get('available') is False or entry.get('enabled') is False or entry.get('status') in ('disabled','offline','unavailable'):
            continue
        candidates.append(ident)
    return candidates


async def verified_models(kind):
    if kind not in ('image','video'):
        raise ValueError('Invalid media type')
    cached = _CACHE.get(kind)
    if cached and time.monotonic()-cached[0] < CACHE_SECONDS:
        return cached[1]
    async with httpx.AsyncClient(timeout=15, follow_redirects=False) as client:
        try:
            response=await client.get(f'{ROOT}/{kind}/models',params={'reliability':'reliable'})
            response.raise_for_status()
            models=_candidates(response.json(),kind)
        except (httpx.HTTPError,ValueError) as exc:
            log.warning('Unable to verify free %s catalog: %s',kind,type(exc).__name__)
            raise NoVerifiedFreeModel(f'Cannot verify free {kind} models right now; no credits were spent.') from None
    _CACHE[kind]=(time.monotonic(),models)
    return models


def _auth():
    key=Config.AI_API_KEY
    if not key or key=='anonymous' or key.startswith('pk_'):
        raise NoVerifiedFreeModel('A valid Pollinations sk_ server-side key is required, even for free models.')
    return {'Authorization': f'Bearer {key}'}


async def generate_media(kind, prompt, width=1024, height=1024, duration=4):
    choices=await verified_models(kind)
    if not choices:
        raise NoVerifiedFreeModel(f'No verified zero-cost {kind} model is currently available. Paid generation was blocked.')
    auth=_auth()
    params={'model': choices[0], 'safe':'true'}
    if kind == 'image':
        params.update(width=width,height=height)
    else:
        params.update(duration=duration,aspectRatio='16:9')
    url=f'{ROOT}/{kind}/{quote(prompt,safe="")}'
    # Only try the first verified free model: automatic retries can generate unexpected cost.
    timeout=httpx.Timeout(600.0,connect=15.0)
    async with httpx.AsyncClient(timeout=timeout, follow_redirects=False) as client:
        try:
            resp=await client.get(url,params=params,headers=auth)
            resp.raise_for_status()
        except httpx.HTTPStatusError as exc:
            code=exc.response.status_code
            raise RuntimeError(f'Free {kind} generation was rejected by provider (HTTP {code}).') from None
        except httpx.HTTPError:
            raise RuntimeError(f'Free {kind} generation request failed. Please try later.') from None
    typ=resp.headers.get('content-type','').lower()
    expected='image/' if kind=='image' else 'video/mp4'
    if not typ.startswith(expected):
        raise RuntimeError(f'Provider did not return a valid {kind} file.')
    if len(resp.content)>48*1024*1024:
        raise RuntimeError('Provider output exceeds Telegram upload limit.')
    bio=io.BytesIO(resp.content)
    bio.name='generated.png' if kind=='image' else 'generated.mp4'
    bio.seek(0)
    return bio,choices[0]
