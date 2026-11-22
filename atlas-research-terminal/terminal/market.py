"""Explicit demo data and public Yahoo market data. Never silently substitute data."""
from __future__ import annotations

import hashlib
from functools import lru_cache
import re
import xml.etree.ElementTree as ET
from datetime import datetime, timedelta, timezone
from email.utils import parsedate_to_datetime
from urllib.parse import quote

import numpy as np
import pandas as pd
import pandas_market_calendars as mcal
import requests

UTC = timezone.utc
NAMES = {"AAPL": "Apple", "MSFT": "Microsoft", "GOOGL": "Alphabet", "AMZN": "Amazon", "TSLA": "Tesla", "NVDA": "NVIDIA", "META": "Meta Platforms"}


def valid_symbol(value):
    return bool(re.fullmatch(r"[A-Z][A-Z0-9.-]{0,9}", value))


def indicators(df):
    df = df.copy()
    close = df.Close
    df["SMA20"] = close.rolling(20).mean()
    df["SMA50"] = close.rolling(50).mean()
    df["EMA20"] = close.ewm(span=20, adjust=False).mean()
    delta = close.diff()
    gain = delta.clip(lower=0).ewm(alpha=1 / 14, adjust=False, min_periods=14).mean()
    loss = (-delta.clip(upper=0)).ewm(alpha=1 / 14, adjust=False, min_periods=14).mean()
    df["RSI"] = (100 - 100 / (1 + gain / loss.replace(0, np.nan))).where(loss.ne(0), 100).where(gain.ne(0) | loss.ne(0), 50)
    df["MACD"] = close.ewm(span=12, adjust=False).mean() - close.ewm(span=26, adjust=False).mean()
    df["MACD_signal"] = df.MACD.ewm(span=9, adjust=False).mean()
    std = close.rolling(20).std()
    df["Upper"] = df.SMA20 + 2 * std
    df["Lower"] = df.SMA20 - 2 * std
    return df


def demo_market(symbol):
    seed = int(hashlib.sha256(symbol.encode()).hexdigest()[:8], 16)
    rng = np.random.default_rng(seed)
    bases = {"AAPL": 225, "MSFT": 420, "GOOGL": 180, "AMZN": 205, "TSLA": 260, "NVDA": 145, "META": 580}
    dates = pd.bdate_range(end=pd.Timestamp.now(tz="UTC").normalize(), periods=180)
    close = bases.get(symbol, 120) * np.exp(np.cumsum(rng.normal(.0008, .016, len(dates))))
    opening = np.r_[close[0], close[:-1]] * (1 + rng.normal(0, .003, len(dates)))
    df = pd.DataFrame({"Open": opening, "High": np.maximum(opening, close) * (1 + rng.uniform(.002, .015, len(dates))), "Low": np.minimum(opening, close) * (1 - rng.uniform(.002, .015, len(dates))), "Close": close, "Volume": rng.integers(8_000_000, 90_000_000, len(dates))}, index=dates)
    return {"symbol": symbol, "name": NAMES.get(symbol, symbol), "frame": indicators(df), "price": float(close[-1]), "previous": float(close[-2]), "asof": datetime.now(UTC).isoformat(), "source": "demo", "currency": "USD"}


def yahoo_market(symbol):
    if not valid_symbol(symbol):
        raise ValueError("Invalid US ticker")
    failures = []
    for host in ("query1.finance.yahoo.com", "query2.finance.yahoo.com"):
        try:
            response = requests.get(f"https://{host}/v8/finance/chart/{quote(symbol)}", params={"range": "1y", "interval": "1d"}, headers={"User-Agent": "Mozilla/5.0 AtlasResearch/1.0"}, timeout=12)
            response.raise_for_status()
            item = response.json()["chart"]["result"][0]
            meta = item["meta"]
            if meta.get("currency") != "USD" or meta.get("instrumentType") != "EQUITY":
                raise ValueError("Only USD equities are supported")
            df = pd.DataFrame(item["indicators"]["quote"][0], index=pd.to_datetime(item["timestamp"], unit="s", utc=True))
            df.columns = [c.title() for c in df.columns]
            df = df[["Open", "High", "Low", "Close", "Volume"]].dropna()
            if len(df) < 60:
                raise ValueError("At least 60 daily bars required")
            price = float(meta["regularMarketPrice"])
            if not np.isfinite(price) or price <= 0:
                raise ValueError("Invalid provider quote")
            return {"symbol": symbol, "name": meta.get("longName", NAMES.get(symbol, symbol)), "frame": indicators(df), "price": price, "previous": float(df.Close.iloc[-2]), "asof": datetime.fromtimestamp(meta["regularMarketTime"], UTC).isoformat(), "source": "yahoo", "currency": "USD"}
        except (requests.RequestException, ValueError, TypeError, KeyError, IndexError) as exc:
            failures.append(type(exc).__name__)
    raise ValueError("Yahoo unavailable (" + ", ".join(failures) + "). Try again later or explicitly select Demo.")


@lru_cache(maxsize=1)
def sentiment_analyzer():
    from vaderSentiment.vaderSentiment import SentimentIntensityAnalyzer
    return SentimentIntensityAnalyzer()


def sentiment(title):
    """VADER compound score; bundled lexicons require no runtime downloads."""
    return sentiment_analyzer().polarity_scores(title)["compound"]


def news(symbol, hours=24):
    response = requests.get("https://feeds.finance.yahoo.com/rss/2.0/headline", params={"s": symbol, "region": "US", "lang": "en-US"}, headers={"User-Agent": "Mozilla/5.0"}, timeout=10)
    response.raise_for_status()
    root = ET.fromstring(response.content)
    items, seen = [], set()
    cutoff = datetime.now(UTC) - timedelta(hours=hours)
    for item in root.findall(".//item"):
        title, link = item.findtext("title", ""), item.findtext("link", "")
        try:
            published = parsedate_to_datetime(item.findtext("pubDate", ""))
            if published.tzinfo is None:
                published = published.replace(tzinfo=UTC)
        except (ValueError, TypeError):
            continue
        if title in seen or published < cutoff or published > datetime.now(UTC) + timedelta(minutes=5):
            continue
        if not link.startswith("https://"):
            continue
        seen.add(title)
        items.append({"title": title, "url": link, "published": published.isoformat(), "sentiment": sentiment(title)})
    return items[:20]


def market_session(now=None):
    now = pd.Timestamp(now or datetime.now(UTC))
    if now.tzinfo is None:
        now = now.tz_localize("UTC")
    schedule = mcal.get_calendar("NYSE").schedule(start_date=(now - pd.Timedelta(days=1)).date(), end_date=(now + pd.Timedelta(days=8)).date())
    for _, row in schedule.iterrows():
        if row.market_open <= now < row.market_close:
            return True, f"Open Â· closes {row.market_close.tz_convert('America/New_York'):%H:%M} ET"
        if row.market_open > now:
            return False, f"Closed Â· next {row.market_open.tz_convert('America/New_York'):%a %H:%M} ET"
    return False, "Closed"


def signal(market, headlines, cfg):
    row = market["frame"].iloc[-1]
    score = float(np.mean([h["sentiment"] for h in headlines])) if headlines else 0.
    checks = {"Price above SMA20": bool(row.Close > row.SMA20), "SMA20 above SMA50": bool(row.SMA20 > row.SMA50), "MACD above signal": bool(row.MACD > row.MACD_signal), "RSI in 35â€“65 range": bool(35 <= row.RSI <= 65), "Positive recent news": bool(headlines and score >= cfg["sentiment_threshold_buy"])}
    alignment = round(sum(checks.values()) / len(checks) * 100)
    if all(checks.values()):
        action, reason = "BUY", "Trend, momentum and headline rules align."
    elif row.RSI >= cfg["rsi_sell_above"] or (headlines and score <= cfg["sentiment_threshold_sell"]):
        action, reason = "SELL", "Elevated RSI or negative headline score; review exposure."
    elif row.RSI < cfg["rsi_buy_below"]:
        action, reason = "HOLD", "Oversold; wait for trend confirmation."
    else:
        action, reason = "HOLD", "Evidence is mixed or recent news is unavailable."
    return {"action": action, "reason": reason, "alignment": alignment, "sentiment": score, "checks": checks}
