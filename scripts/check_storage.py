"""Run after configuring AWS credentials in Hugging Face Space secrets.

Usage: python -m scripts.check_storage
"""
import tempfile
from pathlib import Path
from bot.storage import upload_file, download_file, list_objects


def main():
    with tempfile.TemporaryDirectory() as directory:
        src = Path(directory) / "s3-healthcheck.txt"
        dst = Path(directory) / "downloaded.txt"
        src.write_text("usama-ai-bot-storage verified\n", encoding="utf-8")
        key = upload_file(str(src), "s3-healthcheck.txt")
        download_file("s3-healthcheck.txt", str(dst))
        assert dst.read_bytes() == src.read_bytes(), "S3 content mismatch"
        assert key in [x["key"] for x in list_objects()], "Uploaded object missing"
        print("S3 upload, list, download, and integrity check passed.")


if __name__ == "__main__":
    main()
