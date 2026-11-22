"""Create a source-only release without virtualenvs, account data or secrets."""
from pathlib import Path
import os
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
output = root / "artifacts" / "atlas-research-terminal.zip"
output.parent.mkdir(exist_ok=True)
excluded = {".git", ".venv", "venv", "data", "artifacts", "__pycache__", ".pytest_cache"}
with ZipFile(output, "w", ZIP_DEFLATED) as archive:
    for directory, folders, files in os.walk(root):
        folders[:] = sorted(folder for folder in folders if folder not in excluded)
        for filename in sorted(files):
            file = Path(directory) / filename
            if file.name in {".env", "secrets.toml"} or file.suffix in {".pyc", ".log", ".sqlite3"}:
                continue
            archive.write(file, file.relative_to(root))
print(output)
