# Streamlit Community Cloud handoff

The app is implemented and locally deployed at http://localhost:8501. Community Cloud publication is pending an authenticated Streamlit Cloud deployment session; the parent repository remote is `ai-ml-software/usama-ai-bot`. No public deployment URL has been created or verified.

## Cloud settings

| Setting | Value |
| --- | --- |
| Repository | `ai-ml-software/usama-ai-bot` |
| Branch | `main` or `atlas/research-terminal` |
| Main file | `atlas-research-terminal/app.py` |
| Python | `3.11` |
| Dependencies | `atlas-research-terminal/requirements.txt` |
| Credentials | None needed for demo or public Yahoo data |

For a standalone new repository, upload ZIP contents at its root and select `app.py`. For the existing parent repository, keep the application inside `atlas-research-terminal/` and select the nested entrypoint above. Preserve the `terminal`, `assets` and `.streamlit` directories. Do not upload the `.venv`, `data` or `artifacts` directories. Create the app from the Streamlit Cloud dashboard and select the settings above. Use the available private-sharing/access controls for a private workspace.

After deployment, verify the Overview, record a one-share Demo paper order with a decision note and approval, and confirm its journal entry and CSV download. Switch to Yahoo mode to check provider availability; stale quotes and closed sessions intentionally block market-data paper orders.

Cloud's local filesystem is not a durable ledger. Save your account access key and export backups. Restarts or redeployments may erase SQLite data. Use the included Docker deployment with a persistent volume when durable storage is required.

## Verified locally

- Python 3.11, pinned Streamlit 1.51.0 in `.venv`.
- Project dependency check passed.
- 38 automated tests passed, including ledger concurrency and Streamlit approval flow.
- Actual Chrome acceptance passed: dashboard, simulated fill, journal, CSV download and mobile rendering. Expanded actual Chrome checks also passed for saved full scans and verified backup/recovery.
- Yahoo AAPL quote and recent-headline feeds responded successfully.
- HTTP health endpoint returned 200 / `ok`.
- Docker configuration is included; Docker was unavailable here, so the image was not built or executed.

Use `start.ps1` for the isolated project environment. The initial setup also installed packages into the shared Python environment before isolation; that shared environment has pre-existing/remaining dependency conflicts and is not the verified runtime. All final runtime checks use `.venv`.



## Source publication

Source code is published to [ai-ml-software/usama-ai-bot](https://github.com/ai-ml-software/usama-ai-bot) on `main` and `atlas/research-terminal`. Git authentication now uses Windows Git Credential Manager with the repository owner account. Credentials remain in the operating system credential store and are not included in source control.

Streamlit Cloud app creation is still pending an authenticated Cloud session. Publishing source to GitHub does not by itself create a public Streamlit deployment URL.
