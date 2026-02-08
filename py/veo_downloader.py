#!/usr/bin/env python3
"""
VEO downloader (Windows-safe)
- Uses yt-dlp
- Tracks progress via JSON + DB
- Writes staged downloads before atomic moves
"""

from __future__ import annotations

import copy
import json
import os
import re
import shutil
import subprocess
import sys
import threading
import time
import traceback
from datetime import datetime
from pathlib import Path
from typing import Any, Callable, Dict, Optional

import pymysql
from pymysql.err import ProgrammingError
import yt_dlp
from yt_dlp.utils import DownloadError

# ------------------------------------------------------------------
# Paths
# ------------------------------------------------------------------
BASE_DIR = Path(__file__).resolve().parent.parent
VIDEO_ROOT = BASE_DIR / "videos"
MATCHES_DIR = VIDEO_ROOT / "matches"
TEMP_DOWNLOADS = VIDEO_ROOT / "downloads"
PROGRESS_DIR = BASE_DIR / "storage" / "video_progress"
LOG_FILE = BASE_DIR / "storage" / "logs" / "veo_download.log"
CONFIG_PATH = BASE_DIR / "config" / "config.php"

COOKIE_FILE_ENV = os.environ.get("VEO_COOKIE_FILE")
COOKIE_BROWSER_ENV = os.environ.get("VEO_COOKIE_BROWSER")
DEFAULT_COOKIE_FILE = BASE_DIR / "config" / "veo_cookies.txt"
COOKIE_OPTIONS: Dict[str, str] = {}
COOKIE_SELECTION_LOGGED = False

FFMPEG_PATH = shutil.which("ffmpeg")
FFPROBE_PATH = shutil.which("ffprobe")

THUMBNAIL_OFFSET_SECONDS = 5

progress_state: Dict[str, Any] = {}
progress_lock = threading.Lock()


# --- Exception for yt-dlp spawn errors ---
class YTDLPSpawnError(Exception):
    """Raised when yt-dlp cannot initialize."""


def log(msg: str) -> None:
    LOG_FILE.parent.mkdir(parents=True, exist_ok=True)
    with LOG_FILE.open("a", encoding="utf-8") as f:
        f.write(msg.rstrip() + "\n")


def persist_progress_payload(match_id: int, path: Path, payload: Dict[str, Any]) -> bool:
    tmp = path.parent / (path.stem + ".tmp")
    try:
        tmp.write_text(json.dumps(payload, ensure_ascii=False), encoding="utf-8")
        tmp.replace(path)
        return True
    except Exception as exc:  # pragma: no cover - best effort
        log(f"[match:{match_id}] Failed to persist progress payload: {exc}")
        return False


def timestamp() -> str:
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")


def format_bytes(num: int) -> str:
    if num >= 1024**3:
        return f"{num / (1024**3):.2f} GB"
    if num >= 1024**2:
        return f"{num / (1024**2):.1f} MB"
    if num >= 1024:
        return f"{num / 1024:.1f} KB"
    return f"{num} B"


def generate_thumbnail(
    match_id: int,
    video_path: Path,
    thumbnail_path: Path,
    offset_seconds: int = THUMBNAIL_OFFSET_SECONDS,
) -> bool:
    if FFMPEG_PATH is None:
        log(f"[match:{match_id}] FFmpeg not available; skipping thumbnail generation")
        return False
    if not video_path.exists():
        log(f"[match:{match_id}] Video file missing ({video_path}); skipping thumbnail")
        return False
    try:
        thumbnail_path.parent.mkdir(parents=True, exist_ok=True)
    except OSError as exc:
        log(f"[match:{match_id}] Unable to ensure thumbnail directory: {exc}")
        return False

    if thumbnail_path.exists():
        try:
            thumbnail_path.unlink()
        except OSError as exc:
            log(f"[match:{match_id}] Unable to remove old thumbnail: {exc}")

    cmd = [
        FFMPEG_PATH,
        "-y",
        "-loglevel",
        "error",
        "-ss",
        str(offset_seconds),
        "-i",
        str(video_path),
        "-frames:v",
        "1",
        "-q:v",
        "2",
        str(thumbnail_path),
    ]
    try:
        subprocess.run(cmd, check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        log(f"[match:{match_id}] Thumbnail saved to {thumbnail_path}")
        return True
    except Exception as exc:
        log(f"[match:{match_id}] Thumbnail generation failed: {exc}")
        return False


def get_duration_seconds(match_id: int, video_path: Path) -> Optional[int]:
    if FFPROBE_PATH is None:
        log(f"[match:{match_id}] ffprobe not available; skipping duration detection")
        return None
    if not video_path.exists():
        return None

    try:
        result = subprocess.run(
            [
                FFPROBE_PATH,
                "-v",
                "error",
                "-show_entries",
                "format=duration",
                "-of",
                "default=noprint_wrappers=1:nokey=1",
                str(video_path),
            ],
            capture_output=True,
            text=True,
        )
        if result.returncode != 0:
            return None
        duration_str = (result.stdout or "").strip()
        if not duration_str:
            return None
        return int(float(duration_str))
    except Exception as exc:
        log(f"[match:{match_id}] Failed to get duration: {exc}")
        return None


def parse_config() -> Dict[str, str]:
    cfg: Dict[str, str] = {}
    env_map = {
        "host": "DB_HOST",
        "name": "DB_NAME",
        "user": "DB_USER",
        "pass": "DB_PASS",
        "charset": "DB_CHARSET",
    }

    for key, env_key in env_map.items():
        value = os.environ.get(env_key)
        if value is not None and value != "":
            cfg[key] = value

    if CONFIG_PATH.exists():
        text = CONFIG_PATH.read_text(encoding="utf-8").splitlines()
        in_db = False
        for line in text:
            if not in_db:
                if re.search(r"'db'\s*=>\s*\[", line):
                    in_db = True
                continue

            if re.search(r"^\s*\],", line):
                break

            for key, env_key in env_map.items():
                if key in cfg:
                    continue
                pattern = rf"'{key}'\s*=>\s*(?:getenv\(['\"]{env_key}['\"]\)\s*\?:\s*)?'([^']*)'"
                m = re.search(pattern, line)
                if m:
                    cfg[key] = m.group(1)

    if "pass" not in cfg:
        cfg["pass"] = ""
    if "charset" not in cfg:
        cfg["charset"] = "utf8mb4"

    missing = {"host", "name", "user"} - set(cfg)
    if missing:
        raise RuntimeError(f"Missing DB config keys: {missing}")

    log(f"Using database: {cfg['name']}")
    return cfg


def db_conn():
    cfg = parse_config()
    return pymysql.connect(
        host=cfg["host"],
        user=cfg["user"],
        password=cfg["pass"],
        database=cfg["name"],
        charset=cfg["charset"],
        autocommit=True,
    )


def update_db(
    conn,
    match_id: int,
    status: str,
    percent: int,
    error: Optional[str],
    path: str,
    thumbnail_path: Optional[str] = None,
    duration_seconds: Optional[int] = None,
):
    """
    Updates match_videos row. Handles missing optional columns gracefully.
    """
    # Try with both thumbnail_path + duration_seconds, then fall back if columns are missing.
    sql_full = """
        UPDATE match_videos
        SET download_status=%s,
            download_progress=%s,
            error_message=%s,
            source_path=%s,
            thumbnail_path=%s,
            duration_seconds=%s
        WHERE match_id=%s
        ORDER BY id DESC
        LIMIT 1
    """
    params_full = (status, percent, error, path, thumbnail_path, duration_seconds, match_id)

    sql_thumb_only = """
        UPDATE match_videos
        SET download_status=%s,
            download_progress=%s,
            error_message=%s,
            source_path=%s,
            thumbnail_path=%s
        WHERE match_id=%s
        ORDER BY id DESC
        LIMIT 1
    """
    params_thumb_only = (status, percent, error, path, thumbnail_path, match_id)

    sql_no_optional = """
        UPDATE match_videos
        SET download_status=%s,
            download_progress=%s,
            error_message=%s,
            source_path=%s
        WHERE match_id=%s
        ORDER BY id DESC
        LIMIT 1
    """
    params_no_optional = (status, percent, error, path, match_id)

    try:
        with conn.cursor() as cur:
            cur.execute(sql_full, params_full)
        return
    except ProgrammingError as exc:
        args = exc.args or ()
        err_code = args[0] if args else None
        err_msg = args[1] if len(args) > 1 else ""

        if err_code == 1054 and ("thumbnail_path" in err_msg or "duration_seconds" in err_msg):
            # Retry with whatever is likely available
            if "duration_seconds" in err_msg and "thumbnail_path" not in err_msg:
                # duration missing, thumbnail exists
                with conn.cursor() as cur:
                    cur.execute(sql_thumb_only, params_thumb_only)
                return
            if "thumbnail_path" in err_msg and "duration_seconds" not in err_msg:
                # thumbnail missing, duration exists (rare); easiest: just write without optional
                with conn.cursor() as cur:
                    cur.execute(sql_no_optional, params_no_optional)
                return

            # both missing or ambiguous -> write without optional
            log(f"[match:{match_id}] Optional columns missing; writing DB without thumbnail/duration")
            with conn.cursor() as cur:
                cur.execute(sql_no_optional, params_no_optional)
            return

        raise


def get_cookie_options(match_id: int) -> Dict[str, str]:
    global COOKIE_OPTIONS, COOKIE_SELECTION_LOGGED
    if COOKIE_OPTIONS:
        return COOKIE_OPTIONS

    def _log_selection(message: str) -> None:
        log(f"[match:{match_id}] {message}")

    if COOKIE_FILE_ENV:
        cookie_path = Path(COOKIE_FILE_ENV)
        if cookie_path.is_file():
            COOKIE_OPTIONS["cookiefile"] = str(cookie_path)
            _log_selection(f"Using cookie file from VEO_COOKIE_FILE ({cookie_path})")
            COOKIE_SELECTION_LOGGED = True
            return COOKIE_OPTIONS

    if DEFAULT_COOKIE_FILE.is_file():
        COOKIE_OPTIONS["cookiefile"] = str(DEFAULT_COOKIE_FILE)
        _log_selection(f"Using default cookie file ({DEFAULT_COOKIE_FILE})")
        COOKIE_SELECTION_LOGGED = True
        return COOKIE_OPTIONS

    if COOKIE_BROWSER_ENV:
        COOKIE_OPTIONS["cookiesfrombrowser"] = COOKIE_BROWSER_ENV
        _log_selection(f"Using cookiesfrombrowser ({COOKIE_BROWSER_ENV})")
        COOKIE_SELECTION_LOGGED = True
        return COOKIE_OPTIONS

    if not COOKIE_SELECTION_LOGGED:
        log(f"[match:{match_id}] No cookie source configured; Veo downloads may require authentication")
        COOKIE_SELECTION_LOGGED = True

    return {}


def build_ydl_options(match_id: int, extra: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
    opts: Dict[str, Any] = {
        "quiet": True,
        "no_warnings": True,
    }
    opts.update(get_cookie_options(match_id))
    if extra:
        opts.update(extra)
    return opts


FORMAT_ORDER = ["standard"]
FORMAT_RULES = {
    "standard": {
        "primary": lambda f: (f.get("format_id") or "").lower() == "standard-1080p",
        "fallbacks": [
            lambda f: (f.get("ext") or "").lower() == "mp4"
            and "panorama" not in ((f.get("format_note") or "").lower())
            and "panorama" not in ((f.get("format_id") or "").lower()),
            lambda f: (f.get("ext") or "").lower() == "mp4",
        ],
        "default_id": "standard-1080p",
    },
    "panoramic": {
        "primary": lambda f: (f.get("format_id") or "").lower() == "panorama-2048p",
        "fallbacks": [
            lambda f: "panorama" in ((f.get("format_note") or "").lower())
            or "panorama" in ((f.get("format_id") or "").lower()),
            lambda f: (f.get("ext") or "").lower() == "mp4",
        ],
        "default_id": "panorama-2048p",
    },
}


def detect_formats(
    match_id: int,
    url: str,
    progress_callback: Optional[Callable[..., None]] = None,
) -> Dict[str, Dict[str, int]]:
    log(f"[match:{match_id}] Detecting formats for {url}")
    if progress_callback:
        progress_callback("pending", "metadata", None, "metadata:start", None, None)

    start_time = time.time()
    try:
        ydl_opts = build_ydl_options(match_id, {"skip_download": True})
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=False)
    except Exception as exc:
        error_msg = f"Metadata fetch failed: {exc}"
        log(f"[match:{match_id}] {error_msg}")
        log(traceback.format_exc())
        if progress_callback:
            progress_callback("failed", "metadata", None, error_msg, error_msg, "process_exited_early")
        raise

    formats = info.get("formats") or []
    if not formats:
        log(f"[match:{match_id}] No formats found in metadata")
        raise RuntimeError("No formats reported by yt-dlp")

    detected = []
    for f in formats:
        fid = str(f.get("format_id") or "")
        size = int(f.get("filesize") or f.get("filesize_approx") or 0)
        detected.append(f"{fid} ({format_bytes(size)})")
    log(f"[match:{match_id}] Detected formats: {', '.join(detected)}")

    def pick_format(target: str) -> Dict[str, int]:
        rules = FORMAT_RULES[target]
        candidate = None
        if "primary" in rules:
            candidate = next((f for f in formats if rules["primary"](f)), None)
        if candidate is None:
            for pred in rules.get("fallbacks", []):
                candidate = next((f for f in formats if pred(f)), None)
                if candidate:
                    break

        fmt_id = rules["default_id"]
        total_bytes = 0
        if candidate:
            fmt_id = str(candidate.get("format_id") or fmt_id)
            total_bytes = int(candidate.get("filesize") or candidate.get("filesize_approx") or 0)

        log(
            f"[match:{match_id}] Selected {target} format: {fmt_id} "
            f"({format_bytes(total_bytes) if total_bytes else 'size unknown'})"
        )
        return {"format_id": fmt_id, "total_bytes": total_bytes}

    duration = time.time() - start_time
    if progress_callback:
        progress_callback("pending", "metadata", None, f"metadata:complete ({duration:.1f}s)", None, None)
    log(f"[match:{match_id}] Metadata extraction completed in {duration:.2f}s")
    return {name: pick_format(name) for name in FORMAT_ORDER}


def run(match_id: int, veo_url: str) -> int:
    # Ensure temp downloads directory exists before yt-dlp runs
    try:
        TEMP_DOWNLOADS.mkdir(parents=True, exist_ok=True)
    except Exception as exc:
        log(f"[match:{match_id}] Failed to create temp downloads dir: {exc}")
        return 1

    progress_file = PROGRESS_DIR / f"{match_id}.json"
    cancel_file = progress_file.with_suffix(".cancel")

    # Public path (standard is "primary")
    public_path = f"match_{match_id}_standard.mp4"

    preflight = {
        "python_executable": sys.executable,
        "yt_dlp_version": getattr(yt_dlp, "__version__", "unknown"),
        "cwd": os.getcwd(),
        "uid": getattr(os, "getuid", lambda: None)(),
        "euid": getattr(os, "geteuid", lambda: None)(),
        "gid": getattr(os, "getgid", lambda: None)(),
        "egid": getattr(os, "getegid", lambda: None)(),
        "path": os.environ.get("PATH", ""),
        "ffmpeg": FFMPEG_PATH,
        "ffprobe": FFPROBE_PATH,
    }
    log(f"[match:{match_id}] yt-dlp preflight {json.dumps(preflight, ensure_ascii=False)}")

    format_states = {
        fmt: {
            "downloaded_bytes": 0,
            "total_bytes": 0,
            "approx_total": 0,
            "percent": 0,
        }
        for fmt in FORMAT_ORDER
    }

    def calculate_percent(state: Dict[str, int]) -> int:
        if state["total_bytes"] > 0:
            percent = int((state["downloaded_bytes"] / max(state["total_bytes"], 1)) * 100)
            return min(100, percent)
        if state["downloaded_bytes"] == 0:
            return 0
        approx = max(state["approx_total"], state["downloaded_bytes"], 1)
        percent = int((state["downloaded_bytes"] / approx) * 100)
        return min(95, max(1, percent))

    def overall_percent() -> int:
        values = [state["percent"] for state in format_states.values()]
        return int(sum(values) / max(len(values), 1))

    def aggregate_totals() -> tuple[int, int]:
        downloaded = sum(state["downloaded_bytes"] for state in format_states.values())
        total = sum(state["total_bytes"] for state in format_states.values() if state["total_bytes"] > 0)
        return downloaded, total

    # Open DB connection once
    try:
        conn = db_conn()
    except Exception as exc:
        log(f"[match:{match_id}] Database connection failed: {exc}")
        # Still write a progress file if possible
        PROGRESS_DIR.mkdir(parents=True, exist_ok=True)
        payload = {
            "status": "failed",
            "stage": "db",
            "current_format": "",
            "formats": {},
            "path": public_path,
            "message": f"Database connection failed: {exc}",
            "percent": 0,
            "downloaded_bytes": 0,
            "total_bytes": 0,
            "error": str(exc),
            "error_code": "db_connect_failed",
            "pid": os.getpid(),
            "heartbeat": timestamp(),
            "last_seen_at": timestamp(),
            "updated_at": timestamp(),
            "thumbnail_path": None,
            "duration_seconds": None,
        }
        persist_progress_payload(match_id, progress_file, payload)
        return 1

    stop_event = threading.Event()

    def write_progress(
        status: str,
        stage: str,
        current_format: Optional[str],
        message: str,
        error: Optional[str] = None,
        error_code: Optional[str] = None,
        thumbnail_path: Optional[str] = None,
        duration_seconds: Optional[int] = None,
    ) -> None:
        now = timestamp()
        downloaded_bytes, total_bytes = aggregate_totals()

        payload: Dict[str, Any] = {
            "status": status,
            "stage": stage,
            "current_format": current_format or "",
            "formats": {
                fmt: {
                    "downloaded_bytes": state["downloaded_bytes"],
                    "total_bytes": state["total_bytes"],
                    "percent": state["percent"],
                }
                for fmt, state in format_states.items()
            },
            "path": public_path,
            "message": message,
            "percent": overall_percent(),
            "downloaded_bytes": downloaded_bytes,
            "total_bytes": total_bytes,
            "error": error,
            "error_code": error_code,
            "pid": os.getpid(),
            "heartbeat": now,
            "last_seen_at": now,
            "updated_at": now,
            "thumbnail_path": thumbnail_path,
            "duration_seconds": duration_seconds,
        }

        if persist_progress_payload(match_id, progress_file, payload):
            with progress_lock:
                progress_state.clear()
                progress_state.update(copy.deepcopy(payload))
        else:
            log(f"[match:{match_id}] Failed to persist progress payload")

        # Best-effort DB update
        try:
            update_db(
                conn,
                match_id,
                status,
                overall_percent(),
                error,
                public_path,
                thumbnail_path=thumbnail_path,
                duration_seconds=duration_seconds,
            )
        except Exception as exc:  # pragma: no cover - best effort
            log(f"[match:{match_id}] Failed to update DB: {exc}")

    def heartbeat_loop() -> None:
        while not stop_event.wait(2):
            with progress_lock:
                if not progress_state:
                    continue
                progress_state["heartbeat"] = timestamp()
                snapshot = copy.deepcopy(progress_state)
            persist_progress_payload(match_id, progress_file, snapshot)

    heartbeat_thread = threading.Thread(target=heartbeat_loop, daemon=True)
    heartbeat_thread.start()

    def ensure_directory(path: Path, description: str) -> bool:
        try:
            path.mkdir(parents=True, exist_ok=True)
            return True
        except PermissionError as exc:
            err_msg = f"{description} creation failed: {exc}"
            log(f"[match:{match_id}] {err_msg}")
            write_progress("failed", "spawn", None, err_msg, err_msg, "permission_denied")
            return False

    try:
        if not ensure_directory(LOG_FILE.parent, "logs directory"):
            return 1
        if not ensure_directory(VIDEO_ROOT, "video root directory"):
            return 1
        if not ensure_directory(MATCHES_DIR, "matches root directory"):
            return 1
        if not ensure_directory(PROGRESS_DIR, "progress directory"):
            return 1
        if not ensure_directory(TEMP_DOWNLOADS, "temporary downloads directory"):
            return 1

        log(f"[match:{match_id}] Starting downloader (veo_url={veo_url})")
        write_progress("pending", "spawn", None, "Downloader booting")
        write_progress("pending", "metadata", None, "Preparing downloads")

        # Detect formats
        try:
            format_info = detect_formats(match_id, veo_url, write_progress)
            summary = ", ".join(
                f"{fmt}={info.get('format_id')}"
                for fmt, info in format_info.items()
                if info.get("format_id")
            )
            log(f"[match:{match_id}] Format detection succeeded ({summary or 'no format ids'})")
        except Exception as exc:
            error_msg = f"Metadata fetch failed: {exc}"
            write_progress("failed", "metadata", None, error_msg, error_msg, "process_exited_early")
            return 1

        if not any(info.get("format_id") for info in format_info.values()):
            error_msg = "No downloadable formats found after detection"
            write_progress("failed", "metadata", None, error_msg, error_msg, "process_exited_early")
            return 1

        # Seed totals
        for fmt in FORMAT_ORDER:
            info = format_info.get(fmt, {"total_bytes": 0})
            state = format_states[fmt]
            state["total_bytes"] = int(info.get("total_bytes", 0) or 0)
            state["approx_total"] = max(state["approx_total"], state["total_bytes"])

        def update_format_progress(fmt: str, downloaded: int, total: int) -> None:
            state = format_states[fmt]
            state["downloaded_bytes"] = max(state["downloaded_bytes"], downloaded)
            if total > 0:
                state["total_bytes"] = total
                state["approx_total"] = max(state["approx_total"], total)
            else:
                state["approx_total"] = max(state["approx_total"], downloaded, 1)
            state["percent"] = calculate_percent(state)

        def resolve_downloaded_file(prefix: Path) -> Optional[Path]:
            """
            yt-dlp can produce slightly different filenames; find the most likely output.
            """
            # Common expected mp4
            mp4 = prefix.with_suffix(".mp4")
            if mp4.exists():
                return mp4

            # Try any ext with that stem
            candidates = list(prefix.parent.glob(prefix.name + ".*"))
            candidates = [p for p in candidates if p.is_file()]
            if not candidates:
                return None

            # Prefer mp4 then largest file
            mp4s = [p for p in candidates if p.suffix.lower() == ".mp4"]
            if mp4s:
                return sorted(mp4s, key=lambda p: p.stat().st_size, reverse=True)[0]
            return sorted(candidates, key=lambda p: p.stat().st_size, reverse=True)[0]

        def download_format(fmt: str, fmt_meta: Dict[str, int]) -> Path:
            # Use a base prefix and let yt-dlp add ext; we’ll resolve actual file after.
            prefix = TEMP_DOWNLOADS / f"match_{match_id}_{fmt}"
            stage_name = "download_standard" if fmt == "standard" else "download_panoramic"

            def progress_hook(data: Dict[str, Any]) -> None:
                if cancel_file.exists():
                    log(f"[match:{match_id}] Cancel flag detected, stopping {fmt} download")
                    raise DownloadError("Download cancelled by user")

                downloaded = int(data.get("downloaded_bytes") or data.get("downloaded") or 0)
                total = int(data.get("total_bytes") or data.get("total_bytes_estimate") or 0)

                update_format_progress(fmt, downloaded, total)

                if data.get("status") == "finished":
                    write_progress("downloading", stage_name, fmt, f"{fmt.capitalize()} download finished")
                else:
                    msg = f"Downloading {fmt} video ({format_bytes(downloaded)})"
                    write_progress("downloading", stage_name, fmt, msg)

            ydl_opts = build_ydl_options(
                match_id,
                {
                    "outtmpl": str(prefix) + ".%(ext)s",
                    "noplaylist": True,
                    "progress_hooks": [progress_hook],
                    "merge_output_format": "mp4",
                },
            )
            fmt_id = fmt_meta.get("format_id")
            if fmt_id:
                ydl_opts["format"] = fmt_id

            try:
                ydl = yt_dlp.YoutubeDL(ydl_opts)
            except Exception as exc:
                err_msg = f"yt-dlp initialization failed: {exc}"
                log(f"[match:{match_id}] {err_msg}")
                write_progress("failed", stage_name, fmt, err_msg, err_msg, "yt_dlp_spawn_failed")
                raise YTDLPSpawnError(err_msg) from exc

            log(f"[match:{match_id}] Downloading {fmt} via yt-dlp (format={fmt_id})")
            write_progress("downloading", stage_name, fmt, f"Starting {fmt} download")

            with ydl:
                ydl.download([veo_url])

            out = resolve_downloaded_file(prefix)
            if out is None:
                raise RuntimeError("Temporary download missing")
            return out

        # Download both formats
        for fmt in FORMAT_ORDER:
            stage_name = "download_standard" if fmt == "standard" else "download_panoramic"
            current_meta = format_info.get(fmt, {})

            try:
                temp_path = download_format(fmt, current_meta)
            except DownloadError as exc:
                err_msg = str(exc)
                write_progress("failed", stage_name, fmt, "Download cancelled", err_msg, "process_exited_early")
                log(f"[match:{match_id}] {fmt} download cancelled: {err_msg}")
                if cancel_file.exists():
                    try:
                        cancel_file.unlink()
                    except OSError:
                        pass
                return 1
            except YTDLPSpawnError as exc:
                err_msg = str(exc)
                log(f"[match:{match_id}] {fmt} download aborted before start: {err_msg}")
                return 1
            except Exception as exc:
                err_msg = str(exc)
                log(f"[match:{match_id}] {fmt} download failed: {err_msg}")
                write_progress("failed", stage_name, fmt, f"{fmt.capitalize()} download failed", err_msg, "process_exited_early")
                return 1

            # Finalize path
            dest_dir = MATCHES_DIR
            final_path = dest_dir / f"match_{match_id}_{fmt}.mp4"

            # Update state to 100% based on actual file size
            final_size = temp_path.stat().st_size
            fmt_state = format_states[fmt]
            fmt_state["downloaded_bytes"] = final_size
            fmt_state["total_bytes"] = max(fmt_state["total_bytes"], final_size)
            fmt_state["percent"] = 100

            try:
                temp_path.replace(final_path)  # atomic move within same filesystem
                write_progress("downloading", "finalize", fmt, f"{fmt.capitalize()} video stored at {final_path}")
                log(f"[match:{match_id}] {fmt.capitalize()} moved to {final_path}")
            except Exception as exc:
                err_msg = f"Failed to move {fmt} video: {exc}"
                log(f"[match:{match_id}] {err_msg}")
                write_progress("failed", "finalize", fmt, err_msg, err_msg, "process_exited_early")
                return 1

        # Post-processing: thumbnail + duration (standard)
        thumbnail_relative: Optional[str] = None
        duration_seconds: Optional[int] = None

        standard_video = MATCHES_DIR / f"match_{match_id}_standard.mp4"
        thumbnail_file = MATCHES_DIR / f"thumbnail_{match_id}.jpg"

        if standard_video.exists():
            if generate_thumbnail(match_id, standard_video, thumbnail_file):
                thumbnail_relative = f"thumbnail_{match_id}.jpg"
            duration_seconds = get_duration_seconds(match_id, standard_video)

        # Mark complete (keep schema consistent)

        write_progress(
            "completed",
            "done",
            None,
            "Download complete",
            error=None,
            error_code=None,
            thumbnail_path=thumbnail_relative,
            duration_seconds=duration_seconds,
        )

        # Ensure DB reflects completion (best-effort)

        try:
            update_db(
                conn,
                match_id,
                "completed",
                100,
                None,
                public_path,
                thumbnail_path=thumbnail_relative,
                duration_seconds=duration_seconds,
            )
        except Exception as exc:
            log(f"[match:{match_id}] Final DB update failed: {exc}")

        log(f"[match:{match_id}] Completed successfully")
        return 0

    except Exception as exc:
        err_msg = f"Unhandled exception: {exc}"
        log(f"[match:{match_id}] {err_msg}")
        log(traceback.format_exc())
        try:
            write_progress("failed", "error", None, err_msg, err_msg, "unhandled_exception")
        except Exception:
            pass
        return 1

    finally:
        stop_event.set()
        try:
            heartbeat_thread.join(timeout=2)
        except Exception:
            pass
        try:
            conn.close()
        except Exception:
            pass


def main() -> int:
    if len(sys.argv) != 3:
        print(json.dumps({"status": "error", "error": "invalid_arguments"}))
        return 1
    try:
        match_id = int(sys.argv[1])
    except ValueError:
        print(json.dumps({"status": "error", "error": "invalid_match_id"}))
        return 1
    return run(match_id, sys.argv[2])


if __name__ == "__main__":
    sys.exit(main())
