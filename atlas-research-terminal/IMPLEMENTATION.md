# Atlas implementation checklist

Updated 22 November 2026. This record distinguishes implemented capabilities from deployment and human operating tasks.

| Phase | Result | Evidence / remaining action |
| --- | --- | --- |
| Project structure | Implemented | Modular Streamlit app, configuration, documentation, launcher, Docker and source package |
| Isolated environment | Implemented | Project `.venv`; run `python -m pip check` there |
| Headline sentiment | Implemented | VADER compound score with bundled lexicons; no first-run NLTK downloads |
| Full scan | Implemented | Run Full Scan, per-symbol progress, persistent timestamped decisions and source errors |
| Detailed research | Implemented | OHLCV, RSI, MACD, moving averages, rule evidence and sourced news |
| Paper BUY / SELL | Implemented | Approved, long-only whole-share fills; fees, partial exits and risk checks |
| Journal persistence | Implemented | SQLite is authoritative; filtered CSV export replaces a shared mutable CSV file |
| Restart persistence | Implemented | Save the private access key; resume the same account on the same database |
| Scan preferences | Implemented | Account-scoped watchlist and 5-60 minute interval, default 15 |
| Periodic scans | Implemented | Runs only while the browser session is open; not an unattended scheduler |
| Risk policy review | Implemented | Sidebar policy and Operations review; validated configuration file |
| Backups | Implemented | Versioned JSON export from one read transaction; includes ledger and preferences |
| Recovery | Implemented | Checksum and accounting reconciliation; restores into a new paused account |
| Technical simulation | Implemented | Next-open entries, costs, entry-bar exits, conservative stop/target ambiguity |
| Paper-only execution | Implemented | No broker client, broker keys or live-order path |
| GitHub source publication | Published | Remote `main` and `atlas/research-terminal` verified after authenticated push |
| Streamlit Cloud deployment | Pending account session | Requires authenticated Cloud app creation and verification of the returned URL |
| First market-session observation | User operating task | Cannot be completed in advance or on a closed session |
| Daily/weekly review | User operating task | Review real observations; export backups and scan evidence |
| Personal risk acknowledgements | User decision | No consent or investment-risk acceptance is inferred from this checklist |

## Strategy notes

BUY is five-rule alignment, not probability of profit. Demo does not invent news. Manual paper trades remain available without a BUY signal, but execution risk checks remain mandatory. Stop/target alerts require an approved exit. VADER is not finance-trained. Backtests omit dividends, corporate-action adjustment, taxes and market impact; current news is never replayed into history.

## Verification commands

Run from the application folder:

```powershell
.\.venv\Scripts\python.exe -m pip check
.\.venv\Scripts\python.exe -m pytest -q
python scripts/browser_check.py
```

The browser check requires a running local server and an installed Chrome. Public hosting is not verified by a local health check. Cloud local disk may be reset; recovery exports are important.
