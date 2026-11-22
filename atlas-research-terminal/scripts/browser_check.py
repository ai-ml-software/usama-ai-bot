"""Browser acceptance checks; uses installed Chrome, never downloads a browser."""
from pathlib import Path
import json
from playwright.sync_api import sync_playwright


def run():
    out = Path("artifacts")
    out.mkdir(exist_ok=True)
    with sync_playwright() as p:
        browser = p.chromium.launch(channel="chrome", headless=True)
        page = browser.new_page(viewport={"width": 1536, "height": 1080}, device_scale_factor=1)
        errors = []
        page.on("pageerror", lambda error: errors.append(str(error)))
        page.goto("http://127.0.0.1:8501", wait_until="networkidle")
        page.get_by_role("heading", name="A clearer view of the market.").wait_for()
        page.locator(".js-plotly-plot").first.wait_for()
        page.screenshot(path=str(out / "overview-desktop.png"), full_page=True)
        page.get_by_text("Signal scanner", exact=True).click()
        page.get_by_role("button", name="Run Full Scan", exact=True).last.click()
        page.get_by_text("Full scan saved", exact=False).wait_for()
        page.get_by_label("Saved scan").wait_for()
        with page.expect_download() as evidence:
            page.get_by_role("button", name="Download scan evidence").click()
        evidence.value.save_as(str(out / "acceptance-scan.json"))
        saved_scan = json.loads((out / "acceptance-scan.json").read_text())
        assert len(saved_scan["results"]) == 7
        page.get_by_role("heading", name="From noise to evidence.").scroll_into_view_if_needed()
        page.screenshot(path=str(out / "scanner-desktop.png"), full_page=True)
        page.get_by_text("Paper portfolio", exact=True).click()
        page.get_by_role("heading", name="Capital, under control.").wait_for()
        page.get_by_label("Decision note").fill("Browser acceptance: one-share demo position")
        approval = page.get_by_role("checkbox", name="I approve this simulated paper order")
        approval.focus()
        approval.press("Space")
        assert approval.is_checked()
        page.get_by_role("button", name="Record paper order").click()
        try:
            page.get_by_text("recorded at", exact=False).wait_for(timeout=15000)
        except Exception:
            page.screenshot(path=str(out / "order-failure.png"), full_page=True)
            (out / "order-failure.txt").write_text(page.locator("body").inner_text(), encoding="utf-8")
            raise
        page.screenshot(path=str(out / "portfolio-desktop.png"), full_page=True)
        page.get_by_text("Trade journal", exact=True).click()
        page.locator('[data-testid="stDataFrame"]').wait_for()
        page.get_by_role("button", name="Export filtered journal · CSV").wait_for()
        with page.expect_download() as download:
            page.get_by_role("button", name="Export filtered journal · CSV").click()
        download.value.save_as(str(out / "acceptance-journal.csv"))
        page.get_by_text("Operations", exact=True).click()
        page.get_by_role("heading", name="Confidence in the process.").wait_for()
        with page.expect_download() as backup_download:
            page.get_by_role("button", name="Download verified account backup").click()
        backup_path = out / "acceptance-backup.json"
        backup_download.value.save_as(str(backup_path))
        original = json.loads(backup_path.read_text())
        page.locator('input[type="file"]').set_input_files(str(backup_path))
        recover_approval = page.get_by_role("checkbox", name="Create a new paused account from this backup")
        recover_approval.focus()
        recover_approval.press("Space")
        page.get_by_role("button", name="Validate and recover").click()
        page.get_by_text("Recovery complete.", exact=False).wait_for()
        page.get_by_text("PAUSED", exact=True).wait_for()
        with page.expect_download() as recovered_download:
            page.get_by_role("button", name="Download verified account backup").click()
        recovered_path = out / "acceptance-recovered.json"
        recovered_download.value.save_as(str(recovered_path))
        recovered = json.loads(recovered_path.read_text())
        assert original["payload"]["account"]["cash"] == recovered["payload"]["account"]["cash"]
        assert original["payload"]["positions"] == recovered["payload"]["positions"]
        assert recovered["payload"]["account"]["paused"] == 1
        page.get_by_role("heading", name="Confidence in the process.").scroll_into_view_if_needed()
        page.screenshot(path=str(out / "operations-desktop.png"), full_page=True)
        page.get_by_text("Trade journal", exact=True).click()
        collapse = page.locator('[data-testid="stSidebarCollapseButton"] button')
        if collapse.is_visible():
            collapse.focus()
            collapse.press("Enter")
        page.set_viewport_size({"width": 390, "height": 844})
        page.get_by_role("heading", name="Every decision, accountable.").wait_for()
        page.screenshot(path=str(out / "journal-mobile.png"), full_page=True)
        assert not errors, errors
        assert page.locator('[data-testid="stException"]').count() == 0
        browser.close()
    print("Browser acceptance passed: dashboard, persisted full scan, paper fill, journal export, backup recovery and mobile rendering.")


if __name__ == "__main__":
    run()
