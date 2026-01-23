from __future__ import annotations

import logging
import os
from pathlib import Path

from .config import BASE_DIR

LOG_DIR = BASE_DIR / "storage" / "logs"
LOG_DIR.mkdir(parents=True, exist_ok=True)
LOG_FILE = LOG_DIR / "clip_worker.log"

DEBUG_MODE = os.environ.get("CLIP_WORKER_DEBUG", "").strip().lower() in {"1", "true", "yes"}

LOGGER = logging.getLogger("clip_worker")
LOGGER.setLevel(logging.DEBUG if DEBUG_MODE else logging.INFO)
LOGGER.propagate = False

if not any(isinstance(handler, logging.StreamHandler) for handler in LOGGER.handlers):
    console_handler = logging.StreamHandler()
    console_handler.setLevel(logging.DEBUG if DEBUG_MODE else logging.INFO)
    formatter = logging.Formatter("%(asctime)s clip_worker %(levelname)s: %(message)s", "%Y-%m-%d %H:%M:%S")
    console_handler.setFormatter(formatter)
    LOGGER.addHandler(console_handler)

if not any(isinstance(handler, logging.FileHandler) for handler in LOGGER.handlers):
    file_handler = logging.FileHandler(LOG_FILE, encoding="utf-8")
    file_handler.setLevel(logging.DEBUG if DEBUG_MODE else logging.INFO)
    formatter = logging.Formatter("%(asctime)s clip_worker %(levelname)s: %(message)s", "%Y-%m-%d %H:%M:%S")
    file_handler.setFormatter(formatter)
    LOGGER.addHandler(file_handler)


def info(message: str) -> None:
    LOGGER.info(message)


def warning(message: str) -> None:
    LOGGER.warning(message)


def error(message: str, exc: Exception | None = None) -> None:
    if exc and DEBUG_MODE:
        LOGGER.error(message, exc_info=True)
    else:
        base_message = f"{message}: {exc}" if exc else message
        LOGGER.error(base_message)
