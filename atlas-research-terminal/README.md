# Atlas Research Terminal

A complete Streamlit workspace for US equity research and **paper trading**. Includes a corporate dark interface, interactive OHLCV charts, SMA/EMA/RSI/MACD indicators, transparent signals, recent Yahoo headlines, a transactional paper ledger, manual risk controls, next-open backtests and downloadable reports.

**No real orders are sent.** The signal engine is rule-based, not a trained AI model. Demo prices are synthetic and labeled throughout. No guaranteed returns are claimed.

## Start on Windows

Requires Python 3.11 or 3.12. From this directory:

```powershell
.\start.ps1
```

If your PowerShell policy blocks scripts, use the commands below directly; no policy changes are needed.

```powershell
python -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r requirements.txt
.\.venv\Scripts\python.exe -m streamlit run app.py --server.address=127.0.0.1
```

Open **http://localhost:8501**. Stop the foreground server with Ctrl+C.

## Linux / macOS

```bash
python3 -m venv .venv
.venv/bin/python -m pip install -r requirements.txt
.venv/bin/python -m streamlit run app.py --server.address=127.0.0.1
```

## First use

1. Explore **Overview** in Demo mode. The synthetic prices need no API keys or network access; the market calendar runs locally.
2. Open **Research** for the five rule checks, RSI panel, technical chart, source timestamps and CSV export. Demo does not fabricate news, so its missing news check prevents a BUY signal.
3. Open **Paper portfolio**, select a symbol, enter whole shares and a decision note, approve, and record a simulated order. A BUY signal is not required for a manual research trade. Risk limits still apply.
4. Review holdings, stored stop/target levels, costs and observed account equity. Use SELL for partial or full exits. Pausing new buys still permits exits.
5. Open **Trade journal** to export orders as CSV or download a JSON account backup.
6. Open **Signal scanner** and click **Run Full Scan** for a saved evidence snapshot, per-symbol progress, source coverage and individual rule details. Use 5-60 minute automatic scans while the browser stays open.
7. Open **Operations** to export a version 2 backup or recover one into a new paused account. Recovery verifies the checksum and reconciles the entire trade ledger before writing. Save the new access key after recovery.
8. Save the private UUID from **Resume a paper account** in the sidebar. Enter it later to reopen the same account on this deployment. Browser sessions start fresh accounts unless you resume. Demo and Yahoo mode have separate balances and ledgers.
9. Switch to **Market data · Yahoo Finance** for public USD-equity prices and recent news. Failed data is shown explicitly; the app never silently substitutes synthetic prices.

Anyone with an account key can access that account. Treat it as a password. This is a private-workspace access mechanism, not full identity management. Put a multi-user deployment behind authentication and HTTPS.

## Enforced paper-execution rules

- Whole shares, long only; no leverage, options, shorts or brokerage integration.
- Cash, maximum position count, resulting position allocation and stop-distance risk are checked inside the same SQLite write transaction as the fill.
- All additions count toward the full position limit. Entry fees are included in average cost. Exits report realized P&L after both entry and exit fees.
- Every fill records UTC execution time, source quote time, decision note, simulated slippage and fee.
- Repeated request IDs cannot record duplicate fills. SQLite serializes concurrent writes.
- The daily loss gate latches new buys for the New York day. On a new day the previous observed equity becomes the baseline, so observed overnight losses count. It is not official broker session P&L.
- Market-data orders require an open NYSE session, including holidays and early closes, and quotes within 15 minutes. Yahoo prices may be delayed and unavailable; rejected orders are expected when data is stale.
- Missing held-position marks block orders. The display temporarily values missing holdings at entry cost and labels the estimate.
- Stops and targets are **manual review alerts**, not unattended orders. No trades or notifications run while the application is closed.

## Configuration

Edit `config.json` and restart. Defaults: $100,000 initial paper cash, 8% maximum position allocation, 1% stop-distance position risk, 7% stop distance, 15% target, 4% daily loss gate, six holdings, 5 bps slippage and $1 per order. Initial cash affects newly created accounts only. Stop/target levels already stored on positions do not change when configuration changes. Thresholds affect future decisions and orders.

The news score uses VADER compound sentiment with packaged lexicons, including negation and punctuation rules. No NLTK data download is required. VADER is a general-language method, not a finance-trained model. Missing news prevents BUY. Rule alignment is the fraction of five checks satisfied, **not probability of profit**. Social, earnings, macro events, messaging and broker automation are not implemented or presented as active features.

## Backtesting

Strategy lab uses previous-bar technical checks and the next bar's open for entry. It models configured slippage, fees, position sizing, trend exits and stop/target exits. Stops take precedence when both levels are touched, and adverse gaps fill at the open. Stop/target checking includes the entry bar after the opening fill. If the prior close already triggers a trend exit, the exit occurs at the open before intraday stop/target checks. Final holdings remain marked to close. The buy-and-hold reference excludes costs.

The backtest is technical-only and single-instrument. It omits historical news, the daily portfolio loss gate, dividends, taxes, market impact, and corporate-action adjustments. Raw historical stock splits can distort results. Demo backtests use synthetic data and are software demonstrations, not evidence of investment performance.

## Persistence and backup

SQLite is stored in `data/atlas.sqlite3` (override with the `ATLAS_DB` environment variable). No trading data is committed or included in distribution archives. Export version 2 account JSON from Operations or the journal. Operations validates and restores this file into a new paused account; it never overwrites the current account. Cash, share quantities, average costs and realized P&L must reconcile with the full trade history. The checksum detects corruption, not malicious edits or file authenticity. Account keys are excluded. Legacy unversioned exports are not importable. Scan history is exported separately from Signal scanner. Back up the SQLite database using SQLite's backup API while running, or stop the service before copying the data directory, including WAL files.

```python
import sqlite3
with sqlite3.connect('data/atlas.sqlite3') as source:
    with sqlite3.connect('atlas-backup.sqlite3') as destination:
        source.backup(destination)
```

## Docker deployment

Docker Engine with Compose is required. From the project directory:

```bash
docker compose up -d --build
docker compose ps
```

The service runs as a non-root user, restarts automatically, exposes a health check, and preserves its database in the `atlas-data` named volume. Compose binds to localhost by default. For external access, use an authenticated HTTPS reverse proxy to port 8501. Do not expose this private-workspace app anonymously.

```bash
docker compose logs -f
docker compose down
```

`docker compose down` preserves the volume; `down -v` deletes it.

## Streamlit Community Cloud

Push these source files to a GitHub repository you control, create an app in Streamlit Community Cloud, and select `app.py` as the entrypoint, Python 3.11, and `requirements.txt` as the dependency file. Configure access restriction for private use. This repository includes no credentials and no cloud deployment has been performed automatically.

**Cloud filesystem limitation:** a host restart or redeployment can remove local SQLite data. Use Docker with a persistent volume for a durable ledger, or provide a durable mounted `ATLAS_DB` path. Do not treat an ephemeral Community Cloud filesystem as permanent portfolio storage. Multiple replicas must not independently use separate local ledgers.

## Verify

```powershell
.\.venv\Scripts\python.exe -m pip install -r requirements-dev.txt
.\.venv\Scripts\python.exe -m pytest -q
```

Tests cover accounting, partial exits, persistence, risk rejection, stale quotes, source separation, market calendars, daily loss latching, concurrent duplicate submissions, account isolation, backtest timing and all Streamlit pages, including an approved paper order. The source distribution includes a Python 3.11 CI workflow; when nested in a larger repository, install the workflow at its root or run the commands directly.

With the server running, an optional actual-browser check uses an already-installed Google Chrome:

```powershell
python scripts/browser_check.py
```

It checks the overview, saved full scan, paper order, journal download, backup/recovery and mobile rendering. Screenshots and downloaded CSV are written to ignored `artifacts/`.

Health endpoint: `http://localhost:8501/_stcore/health`.

## Structure

```text
app.py                    Streamlit pages and order approval
assets/style.css          Responsive corporate theme
terminal/market.py        Yahoo, demo, news, calendar and indicators
terminal/portfolio.py     Validated configuration and SQLite ledger
terminal/backtest.py      Technical strategy simulation
terminal/operations.py    Scan audit, preferences and validated recovery
IMPLEMENTATION.md         Checklist with evidence and remaining deployment tasks
tests/                    Core and application acceptance tests
scripts/browser_check.py  Actual browser acceptance workflow
config.json               Risk and strategy settings
.streamlit/config.toml    Theme and server defaults
Dockerfile / compose.yaml Persistent container deployment
start.ps1                 Windows setup and launch
```

Reference documentation: [Streamlit fragments](https://docs.streamlit.io/develop/api-reference/execution-flow/st.fragment) (refresh only while sessions are active), [Docker deployment](https://docs.streamlit.io/deploy/tutorials/docker), [Community Cloud deployment](https://docs.streamlit.io/deploy/streamlit-community-cloud/deploy-your-app).


## Existing parent repository deployment

This installation lives inside `ai-ml-software/usama-ai-bot`. On the Atlas source branch, select **`atlas-research-terminal/app.py`** as the Streamlit Cloud entrypoint and Python 3.11. Community Cloud searches for dependencies next to that file before the repository root, so it uses this application's requirements rather than the parent chatbot's requirements. Application file paths resolve from `app.py`, not the current working directory.

Your account watchlist and scan interval persist in SQLite, separately for Demo and Yahoo modes. Automatic scans are disabled by default and run only in an active browser session. Each saved scan records unavailable symbols and news-feed errors, the configuration and quote timestamps. Saved decisions are research snapshots, never executable quotes. Indicator charts shown inside saved-scan details use the current dataset and are labeled accordingly.

See `IMPLEMENTATION.md` for the supplied checklist mapped to actual implementation and remaining human/cloud tasks.
