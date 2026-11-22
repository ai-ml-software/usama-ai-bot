from pathlib import Path

from streamlit.testing.v1 import AppTest


def test_all_pages_and_paper_order(tmp_path, monkeypatch):
    monkeypatch.setenv("ATLAS_DB", str(tmp_path / "app.sqlite3"))
    app = AppTest.from_file(str(Path("app.py").resolve()), default_timeout=30).run()
    assert not app.exception
    for page in ["Research", "Strategy lab", "Trade journal", "Operations", "Signal scanner", "Methodology", "Paper portfolio"]:
        app.sidebar.radio[0].set_value(page).run()
        assert not app.exception, page
    [x for x in app.text_input if x.label == "Decision note"][0].set_value("UI acceptance test")
    [x for x in app.checkbox if x.label == "I approve this simulated paper order"][0].check()
    [x for x in app.button if x.label == "Record paper order"][0].click().run()
    assert not app.exception
    assert any("recorded" in x.value for x in app.success)
    app.sidebar.radio[0].set_value("Trade journal").run()
    assert not app.exception
    assert len(app.dataframe[0].value) == 1


def test_full_scan_and_saved_watchlist(tmp_path, monkeypatch):
    monkeypatch.setenv("ATLAS_DB", str(tmp_path / "scanner.sqlite3"))
    app = AppTest.from_file(str(Path("app.py").resolve()), default_timeout=30).run()
    [x for x in app.text_input if x.label == "US stock tickers"][0].set_value("AAPL, NVDA")
    [x for x in app.button if x.label == "Update watchlist"][0].click().run()
    app.sidebar.radio[0].set_value("Signal scanner").run()
    [x for x in app.sidebar.button if x.label == "Run Full Scan"][0].click().run()
    assert not app.exception
    assert any("Full scan saved" in x.value for x in app.success)
    assert len(app.dataframe[0].value) == 2
    from terminal.operations import Operations
    from terminal.portfolio import Ledger
    key = app.session_state.access_key + ":demo"
    assert Operations(Ledger(tmp_path / "scanner.sqlite3")).preferences(key, {})["symbols"] == ["AAPL", "NVDA"]


def test_nested_entrypoint_from_other_working_directory(tmp_path):
    import os
    import subprocess
    import sys
    script = str(Path("app.py").resolve())
    env = {**os.environ, "ATLAS_DB": str(tmp_path / "nested.sqlite3")}
    code = "from streamlit.testing.v1 import AppTest; app=AppTest.from_file(" + repr(script) + ",default_timeout=30).run(); assert not app.exception, app.exception"
    result = subprocess.run([sys.executable, "-c", code], cwd=tmp_path, env=env, capture_output=True, text=True, timeout=60)
    assert result.returncode == 0, result.stderr
