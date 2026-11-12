"""Private Hugging Face S3 bucket client using standard S3 environment credentials.

This module intentionally never logs credentials or exposes public object URLs.
"""
import os
from pathlib import Path
from botocore.config import Config as S3Config
import boto3

BUCKET = os.getenv("HF_S3_BUCKET", "usama-ai-bot-storage")
ENDPOINT = os.getenv("HF_S3_ENDPOINT", "https://s3.hf.co/kosama685")
PREFIX = os.getenv("HF_S3_PREFIX", "telegram-bot").strip("/")


def get_client():
    if not os.getenv("AWS_ACCESS_KEY_ID") or not os.getenv("AWS_SECRET_ACCESS_KEY"):
        raise RuntimeError("S3 credentials are not configured in the hosting environment")
    return boto3.client(
        "s3",
        endpoint_url=ENDPOINT,
        region_name="us-east-1",
        config=S3Config(
            region_name="us-east-1",
            s3={"addressing_style": "path"},
            request_checksum_calculation="when_required",
            response_checksum_validation="when_required",
        ),
    )


def key_for(name: str) -> str:
    """Allow only simple object basenames, within our dedicated prefix."""
    if not name or name in {".", ".."} or Path(name).name != name or "\\" in name:
        raise ValueError("Invalid storage object name")
    return f"{PREFIX}/{name}" if PREFIX else name


def upload_file(local_path: str, object_name: str) -> str:
    path = Path(local_path)
    if not path.is_file():
        raise FileNotFoundError(local_path)
    key = key_for(object_name)
    get_client().upload_file(str(path), BUCKET, key)
    return key


def download_file(object_name: str, destination: str) -> str:
    key = key_for(object_name)
    dest = Path(destination)
    dest.parent.mkdir(parents=True, exist_ok=True)
    get_client().download_file(BUCKET, key, str(dest))
    return str(dest)


def list_objects(limit: int = 50) -> list[dict]:
    prefix = f"{PREFIX}/" if PREFIX else ""
    response = get_client().list_objects_v2(Bucket=BUCKET, Prefix=prefix, MaxKeys=min(max(limit, 1), 1000))
    return [{"key": item["Key"], "size": item["Size"]} for item in response.get("Contents", [])]
