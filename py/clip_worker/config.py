import os
from pathlib import Path
from dotenv import load_dotenv

BASE_DIR = Path(__file__).resolve().parent.parent.parent

# Load .env from /py/.env
load_dotenv(BASE_DIR / "py" / ".env")

VIDEO_DIR = BASE_DIR / "videos"
CLIP_OUTPUT_DIR = BASE_DIR / "videos" / "clips"

CLIP_BEFORE_SECONDS = 30
CLIP_AFTER_SECONDS = 30

DB_CONFIG = {
    "user": os.environ.get("CLIP_WORKER_DB_USER"),
    "password": os.environ.get("CLIP_WORKER_DB_PASSWORD"),
    "host": os.environ.get("CLIP_WORKER_DB_HOST", "localhost"),
    "port": int(os.environ.get("CLIP_WORKER_DB_PORT", "3306")),
    "database": os.environ.get("CLIP_WORKER_DB_NAME"),
    "charset": "utf8mb4",
}

REQUIRED_ENV_VARS = [
    "CLIP_WORKER_DB_USER",
    "CLIP_WORKER_DB_PASSWORD",
    "CLIP_WORKER_DB_NAME",
]

missing = [v for v in REQUIRED_ENV_VARS if not os.environ.get(v)]
if missing:
    raise RuntimeError(
        f"Missing required environment variables: {', '.join(missing)}"
    )
