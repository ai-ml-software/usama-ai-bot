# Atlas verification record

22 November 2026. This is an engineering acceptance record, not an investment-performance claim.

- Project environment: Python 3.11, Streamlit 1.51.0, bundled VADER sentiment lexicons.
- Dependency integrity: project `.venv` reports no broken requirements.
- Automated suite: 38 tests, including all eight pages, approved paper fills, scan persistence, account isolation, concurrent duplicate protection, entry-bar stop handling, VADER negation, backup checksums, full accounting reconciliation, corrupted-file rejection and loading the nested application from another working directory.
- Actual Chrome workflow: Overview, full scan and evidence download, approved one-share paper order, journal CSV download, version 2 backup download/upload, recovery into a paused account and equality of recovered holdings/cash. Mobile layout also checked.
- HTTP health endpoint: local port 8501 returns 200 / `ok`.
- Runtime log: no application errors observed during final browser acceptance.
- Source package excludes account data, virtual environments, access keys, secrets and generated artifacts.

## Verification limits

Public Streamlit deployment has not been verified. The connected GitHub account reports read-only access to the parent remote. A noninteractive Git push failed because no authenticated write identity was available. Publishing and authenticated Streamlit app creation remain external access requirements. Docker is provided as configuration and was not built because Docker is not available here. Future trading sessions, ongoing weekly reviews, investment outcomes and a user's personal acknowledgements cannot be checked off in advance.

Use the project virtual environment for execution. The earlier initial setup touched shared Python packages; that shared environment is not the verified runtime. The included launcher explicitly uses `.venv`.
