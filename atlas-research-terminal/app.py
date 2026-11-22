from __future__ import annotations

import html
import json
import os
import platform
import sys
import uuid
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timezone
from pathlib import Path

import pandas as pd
import plotly.graph_objects as go
import streamlit as st
from plotly.subplots import make_subplots

APP_ROOT = Path(__file__).resolve().parent
if str(APP_ROOT) not in sys.path:
    sys.path.insert(0, str(APP_ROOT))

from terminal.backtest import backtest
from terminal.market import NAMES, demo_market, market_session, news, signal, valid_symbol, yahoo_market
from terminal.portfolio import Ledger, Rejected, load_config
from terminal.operations import Operations

st.set_page_config(page_title="Atlas | Research & Paper Trading", page_icon="◈", layout="wide")
st.markdown((APP_ROOT / "assets/style.css").read_text(encoding="utf-8"), unsafe_allow_html=True)
CFG = load_config(APP_ROOT / "config.json")
LEDGER = Ledger(os.getenv("ATLAS_DB") or APP_ROOT / "data/atlas.sqlite3")
OPS = Operations(LEDGER)
GREEN, RED, BLUE = "#4ee1ad", "#ff7788", "#7a9cff"


@st.cache_data(ttl=120, show_spinner=False)
def fetch(symbol, mode, hours):
    try:
        market = demo_market(symbol) if mode == "demo" else yahoo_market(symbol)
        headlines, news_error = [], ""
        if mode == "yahoo":
            try:
                headlines = news(symbol, hours)
            except Exception:
                news_error = "News feed unavailable. Headline confirmation is missing."
        return market, headlines, news_error, ""
    except Exception as exc:
        return None, [], "", str(exc)


@st.cache_data(ttl=60, show_spinner=False)
def session_status():
    return market_session()


def style_chart(fig, height=390):
    fig.update_layout(height=height, paper_bgcolor="rgba(0,0,0,0)", plot_bgcolor="rgba(0,0,0,0)", font=dict(color="#8f9eb5", family="Arial", size=12), margin=dict(l=10, r=10, t=20, b=10), legend=dict(orientation="h", y=1.08, x=0), hovermode="x unified", xaxis_rangeslider_visible=False)
    fig.update_xaxes(showgrid=False, zeroline=False)
    fig.update_yaxes(gridcolor="#1d293a", zeroline=False)
    return fig


def price_chart(market, period, candle=True):
    count = {"1M": 22, "3M": 66, "6M": 132, "1Y": 252}[period]
    df = market["frame"].tail(count)
    fig = make_subplots(rows=2, cols=1, shared_xaxes=True, vertical_spacing=.05, row_heights=[.78, .22])
    if candle:
        fig.add_trace(go.Candlestick(x=df.index, open=df.Open, high=df.High, low=df.Low, close=df.Close, increasing_line_color=GREEN, decreasing_line_color=RED, name="OHLC"), row=1, col=1)
    else:
        fig.add_trace(go.Scatter(x=df.index, y=df.Close, line=dict(color=GREEN, width=2.5), fill="tozeroy", fillcolor="rgba(78,225,173,.045)", name="Close"), row=1, col=1)
    for key, color in (("SMA20", BLUE), ("SMA50", "#d6b16c")):
        fig.add_trace(go.Scatter(x=df.index, y=df[key], line=dict(color=color, width=1.3), name=key), row=1, col=1)
    fig.add_trace(go.Bar(x=df.index, y=df.Volume, marker_color=["#245747" if c >= o else "#633d4a" for c, o in zip(df.Close, df.Open)], name="Volume", showlegend=False), row=2, col=1)
    fig.update_yaxes(tickprefix="$", range=[float(df.Low.min()) * .97, float(df.High.max()) * 1.03], row=1, col=1)
    return style_chart(fig, 440)


def label(text):
    st.markdown(f'<div class="eyebrow">{html.escape(text)}</div>', unsafe_allow_html=True)


def status_card(title, value, detail, color=GREEN):
    st.markdown(f'<div class="stat-card"><div class="stat-label">{html.escape(title)}</div><div class="stat-value" style="color:{color}">{html.escape(str(value))}</div><div class="stat-detail">{html.escape(detail)}</div></div>', unsafe_allow_html=True)


def main():
    if "access_key" not in st.session_state:
        st.session_state.access_key = str(uuid.uuid4())
    with st.sidebar:
        st.markdown('<div class="brand">◈ <b>ATLAS</b><span>RESEARCH TERMINAL</span></div>', unsafe_allow_html=True)
        mode_name = st.selectbox("Workspace", ["Demo · synthetic prices", "Market data · Yahoo Finance"])
        mode = "demo" if mode_name.startswith("Demo") else "yahoo"
        page = st.radio("Navigation", ["Overview", "Signal scanner", "Research", "Paper portfolio", "Strategy lab", "Trade journal", "Operations", "Methodology"], label_visibility="collapsed")
        account_id = f"{st.session_state.access_key}:{mode}"
        LEDGER.create(account_id, mode, CFG["paper_cash"])
        prefs = OPS.preferences(account_id, {"symbols": CFG["symbols"], "scan_interval": 15})
        st.divider()
        label("RESEARCH UNIVERSE")
        with st.form("watchlist"):
            symbols_text = st.text_input("US stock tickers", value=", ".join(prefs["symbols"]), key=f"watchlist_{account_id}")
            apply = st.form_submit_button("Update watchlist", width="stretch")
        if apply:
            symbols = list(dict.fromkeys(s.strip().upper() for s in symbols_text.split(",") if s.strip()))
            try:
                OPS.save_preferences(account_id, symbols, prefs["scan_interval"])
                prefs["symbols"] = symbols
            except ValueError as exc:
                st.error(str(exc))
        if st.button("Run Full Scan", width="stretch", type="primary"):
            st.session_state.scan_request = True
        if st.button("Refresh market data", width="stretch"):
            fetch.clear()
            session_status.clear()
        interval = st.slider("Scan interval (minutes)", 5, 60, value=prefs["scan_interval"], step=5, key=f"interval_{account_id}")
        if interval != prefs["scan_interval"]:
            OPS.save_preferences(account_id, prefs["symbols"], interval)
        auto = st.toggle("Automatic full scans", value=False)
        st.caption("Automatic scans run only while this browser session is open. Your watchlist and interval are saved to this account.")
        with st.expander("Active risk policy"):
            st.json({k: CFG[k] for k in ["max_account_pct_per_position", "risk_pct_per_trade", "stop_pct", "take_profit_pct", "daily_loss_stop_pct", "max_positions"]})
        st.divider()
        with st.expander("Resume a paper account"):
            st.caption("Save this private access key to reopen your ledger. Anyone with the key can access it. Demo and market-data balances are separate.")
            st.code(st.session_state.access_key, language=None)
            key = st.text_input("Existing access key", type="password")
            if st.button("Open account"):
                try:
                    st.session_state.access_key = str(uuid.UUID(key.strip()))
                    st.rerun()
                except ValueError:
                    st.error("Enter a valid UUID access key.")
        st.markdown('<div class="sidebar-footer">PAPER EXECUTION ONLY<br><span>No brokerage connection. No real orders.</span></div>', unsafe_allow_html=True)

    @st.fragment(run_every=interval * 60 if auto else None)
    def workspace():
        render_workspace(mode, page, prefs["symbols"], interval, auto)
    workspace()


def render_workspace(mode, page, watchlist, interval, auto):
    account_id = f"{st.session_state.access_key}:{mode}"
    LEDGER.create(account_id, mode, CFG["paper_cash"])
    account, positions = LEDGER.read(account_id)
    symbols = list(dict.fromkeys(watchlist + list(positions)))
    previous = OPS.scans(account_id, 1)
    due = not previous or (datetime.now(timezone.utc) - datetime.fromisoformat(previous[0]["time"])).total_seconds() >= interval * 60
    requested = st.session_state.pop("scan_request", False)
    full_scan = requested or (auto and due)
    if full_scan:
        fetch.clear()
    progress = st.progress(0, text="Scanning research universe...") if full_scan else None
    data = {}
    with st.spinner("Loading research workspace..."):
        with ThreadPoolExecutor(max_workers=5) as pool:
            futures = {pool.submit(fetch, symbol, mode, CFG["news_lookback_hours"]): symbol for symbol in symbols}
            for future in as_completed(futures):
                symbol = futures[future]
                try:
                    data[symbol] = future.result()
                except Exception as exc:
                    data[symbol] = (None, [], "", f"Scan failed: {type(exc).__name__}")
                if progress:
                    progress.progress(len(data) / len(symbols), text=f"Scanned {len(data)}/{len(symbols)} | {symbol}")
    data = {symbol: data[symbol] for symbol in symbols}
    if full_scan:
        results = []
        for symbol, (market, headlines, news_error, error) in data.items():
            if market is None:
                results.append({"symbol": symbol, "action": "UNAVAILABLE", "error": error})
                continue
            sig = signal(market, headlines, CFG)
            results.append({"symbol": symbol, "price": market["price"], "asof": market["asof"], "source": market["source"], "rsi": float(market["frame"].RSI.iloc[-1]), "stop_reference": market["price"] * (1 - CFG["stop_pct"] / 100), "target_reference": market["price"] * (1 + CFG["take_profit_pct"] / 100), "news_count": len(headlines), "news_error": news_error, **sig})
        OPS.record_scan(account_id, mode, {**CFG, "symbols": symbols, "scan_interval_minutes": interval}, results)
        progress.empty()
        st.success(f"Full scan saved | {len(symbols)} instruments | {sum(r['action'] == 'UNAVAILABLE' for r in results)} unavailable.")
    markets = {s: v[0] for s, v in data.items() if v[0] is not None}
    marks = {s: m["price"] for s, m in markets.items()}
    complete = all(s in marks for s in positions)
    if complete:
        LEDGER.mark(account_id, marks, CFG)
        account, positions = LEDGER.read(account_id)
    equity = LEDGER.equity(account, positions, marks)
    session_open, session_label = session_status()
    top, clock = st.columns([3, 2])
    with top:
        label("ATLAS / " + page.upper())
        st.title({"Overview": "A clearer view of the market.", "Signal scanner": "From noise to evidence.", "Operations": "Confidence in the process.", "Research": "Evidence before execution.", "Paper portfolio": "Capital, under control.", "Strategy lab": "Test the thesis.", "Trade journal": "Every decision, accountable.", "Methodology": "Know what drives the signal."}[page])
    with clock:
        st.markdown(f'<div class="session"><span class="mode-pill">{"DEMO WORKSPACE" if mode == "demo" else "PUBLIC MARKET DATA"}</span><p>{html.escape(session_label)}</p><small>{datetime.now(timezone.utc):%d %b %Y · %H:%M UTC}</small></div>', unsafe_allow_html=True)
    st.caption("An independent research and paper-trading workspace. Educational use; signals are rule-based research, not personalized investment advice.")
    if mode == "demo":
        st.info("Demo mode · All prices and charts are deterministic synthetic examples. News confirmation is unavailable. Paper fills are simulated.", icon="ℹ️")
    else:
        st.caption("Yahoo public data may be delayed or unavailable. Paper orders require an open session and a quote no older than 15 minutes.")
    for s, result in data.items():
        if result[3]:
            st.warning(f"{s}: {result[3]}")
    if not complete:
        st.error("Some held positions have no quote. Equity uses entry-cost placeholders for those holdings; orders are blocked until all marks are available.")
    pnl = equity - account["initial"]
    day_pnl = equity - account["baseline"]
    cols = st.columns(4)
    with cols[0]: status_card("PAPER NET LIQUIDATION" + (" · ESTIMATED" if not complete else ""), f"${equity:,.2f}", f"{pnl / account['initial']:+.2%} since inception", "#e7eef8")
    with cols[1]: status_card("AVAILABLE CASH", f"${account['cash']:,.2f}", f"{account['cash'] / equity:.1%} cash allocation" if equity > 0 else "No buying power", "#e7eef8")
    with cols[2]: status_card("DAY CHANGE", f"{day_pnl:+,.2f}", f"{day_pnl / account['baseline']:+.2%} vs. day baseline", GREEN if day_pnl >= 0 else RED)
    with cols[3]: status_card("RISK GATE", "LOCKED" if account["loss_locked"] else "PAUSED" if account["paused"] else "ACTIVE", f"{len(positions)} / {CFG['max_positions']} position slots", RED if account["loss_locked"] or account["paused"] else GREEN)
    st.write("")
    if page == "Overview":
        overview(markets, data, account, positions, marks)
    elif page == "Signal scanner":
        scanner(account_id, markets, data)
    elif page == "Operations":
        operations(account_id, mode, complete, markets)
    elif page == "Research":
        research(markets, data)
    elif page == "Paper portfolio":
        portfolio(account_id, account, positions, markets, marks, session_open, complete)
    elif page == "Strategy lab":
        lab(markets, mode)
    elif page == "Trade journal":
        journal(account_id)
    else:
        methodology()
    st.divider()
    st.caption("ATLAS  /  RESEARCH WITH DISCIPLINE                                     PAPER ONLY · USD EQUITIES · NO LEVERAGE")


def select_market(markets, key):
    if not markets:
        st.info("No market data is available. Refresh or switch explicitly to Demo.")
        return None
    return st.selectbox("Instrument", list(markets), format_func=lambda s: f"{s} · {markets[s]['name']}", key=key)


def overview(markets, data, account, positions, marks):
    left, right = st.columns([2.5, 1], gap="large")
    with left:
        with st.container(border=True):
            label("MARKET LENS")
            c1, c2 = st.columns([2, 1])
            with c1: selected = select_market(markets, "overview_symbol")
            with c2: period = st.selectbox("Chart range", ["1M", "3M", "6M", "1Y"], index=1)
            if selected:
                market = markets[selected]
                change = market["price"] / market["previous"] - 1
                st.markdown(f"### {selected}  ·  ${market['price']:,.2f}")
                st.caption(f"{change:+.2%} vs previous close · quote {market['asof'][:19]} UTC · {market['source'].upper()}")
                st.plotly_chart(price_chart(market, period, False), width="stretch")
    with right:
        with st.container(border=True):
            label("THE DISCIPLINE DESK")
            st.subheader("Protect the downside.")
            st.caption("Limits checked again at execution, against the current paper account.")
            for title, value in [("Position allocation", f"≤ {CFG['max_account_pct_per_position']}%"), ("Risk per position", f"≤ {CFG['risk_pct_per_trade']}%"), ("Stop distance", f"{CFG['stop_pct']}%"), ("Profit target", f"+{CFG['take_profit_pct']}%"), ("Daily loss gate", f"−{CFG['daily_loss_stop_pct']}%")]:
                st.markdown(f'<div class="risk-row"><span>{title}</span><b>{value}</b></div>', unsafe_allow_html=True)
            st.caption("Stops are review alerts. They execute only when you approve a paper exit; nothing runs while the app is closed.")
        with st.container(border=True):
            label("EXPOSURE")
            gross = sum(p["qty"] * marks.get(s, p["entry"]) for s, p in positions.items())
            total = account["cash"] + gross
            st.progress(min(1., gross / total) if total > 0 else 0.)
            st.caption(f"${gross:,.0f} deployed · ${account['cash']:,.0f} in reserve")
    label("WATCHLIST / SIGNAL MONITOR")
    rows = []
    for s, market in markets.items():
        sig = signal(market, data[s][1], CFG)
        row = market["frame"].iloc[-1]
        rows.append({"Ticker": s, "Company": market["name"], "Price": market["price"], "Change %": (market["price"] / market["previous"] - 1) * 100, "Signal": sig["action"], "Rule alignment %": sig["alignment"], "RSI": row.RSI, "News items": len(data[s][1])})
    st.dataframe(pd.DataFrame(rows), hide_index=True, width="stretch", column_config={"Price": st.column_config.NumberColumn(format="$%.2f"), "Change %": st.column_config.NumberColumn(format="%+.2f%%"), "RSI": st.column_config.NumberColumn(format="%.1f"), "Rule alignment %": st.column_config.ProgressColumn(min_value=0, max_value=100, format="%d%%")})


def research(markets, data):
    selected = select_market(markets, "research_symbol")
    if not selected: return
    market, headlines = markets[selected], data[selected][1]
    sig, row = signal(market, headlines, CFG), market["frame"].iloc[-1]
    left, right = st.columns([2, 1], gap="large")
    with left:
        st.plotly_chart(price_chart(market, "6M"), width="stretch")
        label("MOMENTUM / RSI 14")
        rsi = go.Figure(go.Scatter(x=market["frame"].index, y=market["frame"].RSI, line_color=BLUE, name="RSI"))
        rsi.add_hline(y=70, line_dash="dot", line_color=RED)
        rsi.add_hline(y=30, line_dash="dot", line_color=GREEN)
        rsi.update_yaxes(range=[0, 100])
        st.plotly_chart(style_chart(rsi, 190), width="stretch")
    with right:
        with st.container(border=True):
            label("EXPLAINABLE SIGNAL")
            st.subheader(f"{sig['action']} · {sig['alignment']}% alignment")
            st.write(sig["reason"])
            st.caption("Alignment counts satisfied rules. It is not a probability of profit or an AI confidence estimate.")
            for title, ok in sig["checks"].items():
                st.write(f"{'✓' if ok else '○'}  {title}")
            st.divider()
            st.write(f"RSI **{row.RSI:.1f}** · MACD **{row.MACD:.2f}**")
            st.caption(f"VADER compound score: {sig['sentiment']:+.2f} · {len(headlines)} recent items")
        st.download_button("Download daily OHLCV + indicators", market["frame"].to_csv(), f"{selected}_research.csv", "text/csv", width="stretch")
    label("NEWS / SOURCE EVIDENCE")
    if data[selected][2]: st.warning(data[selected][2])
    if not headlines:
        st.info("No recent news evidence. Demo does not fabricate headlines; a BUY signal requires positive recent news.")
    for item in headlines:
        with st.container(border=True):
            st.link_button(item["title"], item["url"])
            st.caption(f"{item['published'][:19]} · VADER compound {item['sentiment']:+.2f}")


def portfolio(account_id, account, positions, markets, marks, session_open, complete):
    left, right = st.columns([2, 1], gap="large")
    with left:
        label("OPEN POSITIONS")
        rows, triggered = [], []
        for s, p in positions.items():
            price = marks.get(s, p["entry"])
            alert = "STOP REVIEW" if price <= p["stop"] else "TARGET REVIEW" if price >= p["target"] else "Within levels"
            if alert != "Within levels": triggered.append(f"{s}: {alert}")
            rows.append({"Ticker": s, "Shares": p["qty"], "Avg cost": p["entry"], "Mark": price, "Value": price * p["qty"], "Unrealized P&L": (price - p["entry"]) * p["qty"], "Stop": p["stop"], "Target": p["target"], "Status": alert})
        if rows:
            st.dataframe(pd.DataFrame(rows), hide_index=True, width="stretch", column_config={k: st.column_config.NumberColumn(format="$%.2f") for k in ["Avg cost", "Mark", "Value", "Unrealized P&L", "Stop", "Target"]})
        else:
            st.info("Your account starts in cash. Use the paper order ticket to record your first simulated trade.")
        for item in triggered: st.warning(item + " — select SELL and approve an exit in the ticket.")
        if account["loss_locked"]:
            st.error("Daily loss limit reached. New buys remain locked for the trading day. Review and approve any position exits.")
        history = pd.DataFrame(LEDGER.history(account_id))
        label("ACCOUNT EQUITY / OBSERVED SNAPSHOTS")
        if len(history) > 1:
            fig = go.Figure(go.Scatter(x=pd.to_datetime(history.time), y=history.equity, line_color=GREEN, name="Paper equity"))
            st.plotly_chart(style_chart(fig, 270), width="stretch")
        else:
            st.caption("Equity history builds as you use the workspace; no historical returns are invented.")
        paused = st.toggle("Pause new paper buys", value=bool(account["paused"]))
        if paused != bool(account["paused"]):
            LEDGER.pause(account_id, paused)
            st.rerun()
    with right:
        with st.container(border=True):
            label("PAPER ORDER TICKET")
            selected = select_market(markets, "order_symbol")
            if selected:
                side = st.selectbox("Side", ["BUY", "SELL"])
                market = markets[selected]
                st.write(f"Reference quote **${market['price']:,.2f}**")
                st.caption(f"As of {market['asof'][:19]} UTC")
                equity = LEDGER.equity(account, positions, marks)
                max_total = int(min(equity * CFG["max_account_pct_per_position"] / 100 / (market["price"] * (1 + CFG["slippage_bps"] / 10000)), max(0, equity * CFG["risk_pct_per_trade"] / 100 - CFG["fee_per_order"]) / (market["price"] * (1 + CFG["slippage_bps"] / 10000) * CFG["stop_pct"] / 100)))
                additional = max(0, max_total - positions.get(selected, {}).get("qty", 0))
                st.caption(f"Allocation/risk capacity: {additional} additional shares; cash checked at execution.")
                with st.form("order"):
                    qty = st.number_input("Whole shares", min_value=1, max_value=1_000_000, value=1, step=1)
                    reason = st.text_input("Decision note", placeholder="Record your investment thesis or exit reason")
                    approved = st.checkbox("I approve this simulated paper order")
                    submit = st.form_submit_button("Record paper order", type="primary", width="stretch", disabled=not complete)
                if submit:
                    if not approved or not reason.strip():
                        st.error("Approve the order and add a decision note.")
                    else:
                        token = st.session_state.setdefault("order_token", str(uuid.uuid4()))
                        try:
                            fill = LEDGER.execute(account_id, side, int(qty), market, marks, CFG, token, reason, session_open, {s: m["asof"] for s, m in markets.items()})
                            st.session_state.order_token = str(uuid.uuid4())
                            st.session_state.order_notice = f"{side} {qty} {selected} recorded at ${fill['fill']:,.4f}; fee ${fill['fee']:.2f}."
                            st.rerun()
                        except Rejected as exc:
                            st.error(str(exc))
                st.caption(f"Simulated slippage: {CFG['slippage_bps']} bps · fee: ${CFG['fee_per_order']:.2f}/order. Long-only, whole shares.")
        if "order_notice" in st.session_state:
            st.success(st.session_state.pop("order_notice"))


def lab(markets, mode):
    st.info("Technical-only historical simulation. Signals use the previous completed bar and enter at the next open. Historical news is not replayed. Results exclude taxes, dividends and corporate-action adjustments; splits can distort raw-price history.")
    selected = select_market(markets, "lab_symbol")
    if not selected: return
    with st.expander("Simulation assumptions", expanded=False):
        st.json({k: CFG[k] for k in ["paper_cash", "max_account_pct_per_position", "risk_pct_per_trade", "stop_pct", "take_profit_pct", "slippage_bps", "fee_per_order"]})
        st.caption("Single instrument. Stop/target exits are checked on the entry bar after the opening fill. If both levels are touched, stop takes precedence. Final positions are marked, not forcibly closed. Daily account gate is not modeled here.")
    curve, trades = backtest(markets[selected]["frame"], CFG)
    cols = st.columns(3)
    with cols[0]: st.metric("Simulated total return", f"{curve.equity.iloc[-1] / CFG['paper_cash'] - 1:+.2%}")
    with cols[1]: st.metric("Maximum drawdown", f"{curve.drawdown.min():.2%}")
    with cols[2]: st.metric("Closed trades", len(trades[trades.side == "SELL"]) if not trades.empty else 0)
    fig = go.Figure()
    for column, name, color in [("equity", "Technical strategy", GREEN), ("benchmark", "Buy & hold reference (no costs)", BLUE)]:
        fig.add_trace(go.Scatter(x=curve.date, y=curve[column], name=name, line_color=color))
    st.plotly_chart(style_chart(fig, 420), width="stretch")
    if mode == "demo": st.caption("This simulation uses synthetic data. It is a software demonstration, not market performance evidence.")
    if not trades.empty:
        st.dataframe(trades, hide_index=True, width="stretch")
        st.download_button("Export simulation trades", trades.to_csv(index=False), f"{selected}_backtest.csv", "text/csv")
    else: st.info("No trades met the entry rules in this sample.")


def journal(account_id):
    trades = pd.DataFrame(LEDGER.trades(account_id))
    label("PERSISTENT AUDIT TRAIL")
    if trades.empty:
        st.info("No paper orders recorded yet. Approved orders appear here with fill costs, source timestamps and decision notes.")
        return
    c1, c2, c3 = st.columns(3)
    c1.metric("Recorded orders", len(trades))
    c2.metric("Realized P&L, after fees", f"${trades.realized.sum():+,.2f}")
    c3.metric("Execution fees", f"${trades.fee.sum():,.2f}")
    selected = st.multiselect("Filter side", ["BUY", "SELL"], default=["BUY", "SELL"])
    filtered = trades[trades.side.isin(selected)]
    st.dataframe(filtered, hide_index=True, width="stretch")
    # Spreadsheet-safe text export: prevent formula execution from user notes.
    exported = filtered.copy()
    for column in exported.select_dtypes(include="object"):
        exported[column] = exported[column].map(lambda x: "'" + x if isinstance(x, str) and x.lstrip().startswith(("=", "+", "-", "@", "\t", "\r")) else x)
    st.download_button("Export filtered journal · CSV", exported.to_csv(index=False), "atlas_trade_journal.csv", "text/csv")
    st.download_button("Download account backup · JSON", json.dumps(OPS.backup(account_id), indent=2), "atlas_account_backup.json", "application/json")


def methodology():
    st.subheader("Transparent by design")
    st.write("Atlas is a deterministic research engine, not a trained AI model. Five equal-weight checks evaluate trend, momentum, RSI and recent headline sentiment. BUY requires all five checks. SELL flags elevated RSI or negative recent headlines. Other combinations produce HOLD.")
    st.write("Headline sentiment uses VADER with bundled lexicons, including punctuation and negation rules. It is a general-language model, not a finance-trained model, and can misread relevance, financial terminology or sarcasm. No social feed, earnings calendar or macro-event filter is claimed. Missing news prevents a BUY signal.")
    st.subheader("Execution and account rules")
    st.write("All orders are simulated and require approval. Allocation and stop-distance risk are checked on the entire resulting position, including additions. Entry fees are included in average cost; realized P&L includes entry and exit fees. Stops and targets are stored at entry and shown for manual review. Pausing blocks buys while permitting exits.")
    st.write("The daily loss gate compares marked equity with the last observed equity carried into the New York trading day. It locks new buys for that day once observed. Missing marks are shown at entry cost and block orders. This is an observed account baseline, not a broker-calculated official session P&L.")
    st.subheader("Data and persistence")
    st.write("Demo prices are reproducible synthetic paths. Yahoo data is fetched from public endpoints, cached for two minutes, and displayed with its provider timestamp. Trading calendars include NYSE holidays and early closes. Paper orders in market-data mode require an open session and quotes within 15 minutes. Data availability, licensing and delays are controlled by the provider.")
    st.write("SQLite stores accounts, positions, approved orders and equity snapshots. Keep the private access key to resume an account. This bearer key is a convenience for a private workspace; use authenticated hosting before exposing the application to other people. Back up the database or export accounts. A local disk is not durable on all cloud hosts.")
    st.subheader("Research limitations")
    st.write("There is no guaranteed return. Backtests simplify execution and omit market impact, taxes, dividends and corporate-action adjustments. Current news cannot be applied to historical decisions. The app does not send messages, connect to a broker, or run unattended trades.")
    st.download_button("Download risk configuration", json.dumps(CFG, indent=2), "config.json", "application/json")


def scanner(account_id, markets, data):
    left, right = st.columns([3, 1])
    with left:
        st.write("Capture an auditable view of every symbol, including unavailable data. Each saved scan retains its source timestamps, rule checks and risk configuration.")
    with right:
        if st.button("Run Full Scan", type="primary", width="stretch", key="scanner_run"):
            st.session_state.scan_request = True
            st.rerun()
    scans = OPS.scans(account_id)
    if not scans:
        st.info("Run your first full scan to create a persistent research snapshot.")
        return
    chosen = st.selectbox("Saved scan", [s["id"] for s in scans], format_func=lambda key: next(f"{s['time'][:19]} UTC | {len(s['results'])} symbols | {key[:8]}" for s in scans if s["id"] == key))
    snapshot = next(s for s in scans if s["id"] == chosen)
    rows = snapshot["results"]
    cols = st.columns(4)
    for col, action, title in zip(cols, ["BUY", "HOLD", "SELL", "UNAVAILABLE"], ["Buy alignment", "Wait / observe", "Review exposure", "Data unavailable"]):
        col.metric(title, sum(r["action"] == action for r in rows))
    st.caption("Displayed results are saved observations, not fresh execution quotes. Stop/target references are indicative; held-position levels are stored separately.")
    table = pd.DataFrame([{"Ticker": row["symbol"], "Signal": row["action"], "Price": row.get("price"), "Rule alignment %": row.get("alignment", 0), "RSI": row.get("rsi"), "News": row.get("news_count", 0), "Reason": row.get("reason", row.get("error", ""))} for row in rows])
    st.dataframe(table, hide_index=True, width="stretch", column_config={"Price": st.column_config.NumberColumn(format="$%.2f"), "RSI": st.column_config.NumberColumn(format="%.1f"), "Rule alignment %": st.column_config.ProgressColumn(min_value=0, max_value=100, format="%d%%")})
    for row in rows:
        with st.expander(f"{row['symbol']} | {row['action']} | decision evidence"):
            if row["action"] == "UNAVAILABLE":
                st.warning(row["error"])
                continue
            st.write(row["reason"])
            for name, passed in row["checks"].items():
                st.write(f"{'PASS' if passed else 'MISSING'} {name}")
            st.caption(f"RSI {row['rsi']:.1f} | VADER {row['sentiment']:+.3f} | {row['news_count']} recent articles | source {row['source']} | {row['asof']}")
            st.write(f"Reference stop **${row['stop_reference']:.2f}** | reference target **${row['target_reference']:.2f}**")
            if row.get("news_error"): st.warning(row["news_error"])
            if row["symbol"] in markets:
                st.plotly_chart(price_chart(markets[row["symbol"]], "3M"), width="stretch", key=f"scan_chart_{row['symbol']}")
                st.caption("Chart uses the current available dataset, not the historical saved scan dataset.")
    st.download_button("Download scan evidence | JSON", json.dumps({"id": snapshot["id"], "time": snapshot["time"], "mode": snapshot["mode"], "config": snapshot["config"], "results": rows}, indent=2), "atlas_scan_evidence.json", "application/json")


def operations(account_id, mode, complete, markets):
    label("WORKSPACE READINESS")
    cols = st.columns(3)
    cols[0].metric("Execution", "PAPER ONLY")
    cols[1].metric("Available instruments", len(markets))
    cols[2].metric("Held-position coverage", "Complete" if complete else "Missing quotes")
    with st.container(border=True):
        st.subheader("Runtime & data integrity")
        st.write(f"Python {platform.python_version()} | Streamlit {st.__version__} | SQLite WAL ledger")
        st.caption("UTC source timestamps and New York session calendar. Public quotes are not guaranteed real-time. No broker credentials or execution integration exist.")
        scans = OPS.scans(account_id, 1)
        st.write(f"Latest saved scan: {scans[0]['time'][:19] + ' UTC' if scans else 'No scan recorded'}")
        st.json({k: CFG[k] for k in ["paper_cash", "max_account_pct_per_position", "risk_pct_per_trade", "stop_pct", "take_profit_pct", "daily_loss_stop_pct", "max_positions"]})
    left, right = st.columns(2, gap="large")
    with left:
        with st.container(border=True):
            st.subheader("Portable account backup")
            st.write("Export one consistent snapshot of cash, holdings, trade history, equity observations and saved preferences. The backup excludes your account access key.")
            st.download_button("Download verified account backup", json.dumps(OPS.backup(account_id), indent=2), "dashboard_state.json", "application/json", width="stretch")
            st.caption("Checksum detects file corruption; it is not a digital signature. Scans stay in the local database and are exported separately from Signal scanner.")
    with right:
        with st.container(border=True):
            st.subheader("Recover into a new account")
            st.write("Recovery reconciles cash, quantities, average cost and realized P&L against every trade. It creates a new paused account and leaves the current account intact.")
            uploaded = st.file_uploader("Atlas version 2 backup | JSON, up to 10 MB", type=["json"], key=f"backup_{account_id}")
            approved = st.checkbox("Create a new paused account from this backup", key="approve_restore")
            if st.button("Validate and recover", width="stretch", disabled=not uploaded or not approved):
                try:
                    if uploaded.size > 10 * 1024 * 1024:
                        raise ValueError("Backup exceeds 10 MB.")
                    backup = json.loads(uploaded.getvalue())
                    restored = OPS.restore(backup, mode)
                    st.session_state.access_key = restored.rsplit(":", 1)[0]
                    st.session_state.recovery_notice = "Recovery complete. Save the new access key in the sidebar. New buys are paused until you review the restored account."
                    st.rerun()
                except (ValueError, TypeError, KeyError) as exc:
                    st.error(f"Recovery rejected: {exc}")
    if "recovery_notice" in st.session_state:
        st.success(st.session_state.pop("recovery_notice"))
    st.subheader("Deployment readiness")
    st.write("Local execution is available. Streamlit Cloud requires publishing the source branch to GitHub and creating the app in an authenticated Cloud session. Cloud local files may be reset, so export backups regularly.")
    st.download_button("Download implementation checklist", (APP_ROOT / "IMPLEMENTATION.md").read_text(encoding="utf-8"), "atlas_implementation.md", "text/markdown")


if __name__ == "__main__":
    main()
