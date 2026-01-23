from __future__ import annotations

import json
import os
import time
from pathlib import Path
from typing import Any, Dict, Optional, Tuple

from .config import BASE_DIR, VIDEO_DIR, CLIP_OUTPUT_DIR
from .db import get_connection
from .ffmpeg import generate_clip
from .jobs import (
    ClipJob,
    fetch_next_job,
    mark_job_completed,
    mark_job_failed,
    mark_job_processing,
)
from .logger import error, info

POLL_INTERVAL = float(os.environ.get("CLIP_WORKER_POLL_INTERVAL", "5"))
PHASE_DISABLED_SLEEP = float(os.environ.get("CLIP_WORKER_DISABLED_SLEEP", "10"))

# If you allow "output_path" overrides in payload, set to "1".
ALLOW_OUTPUT_OVERRIDE = os.environ.get("CLIP_WORKER_ALLOW_OUTPUT_OVERRIDE", "0").strip().lower() in {
    "1",
    "true",
    "yes",
}


def dry_run_enabled() -> bool:
    value = os.environ.get("CLIP_WORKER_DRY_RUN")
    if value is None:
        return False
    normalized = value.strip().lower()
    return normalized in {"1", "true", "yes"}


def phase3_is_enabled() -> bool:
    value = os.environ.get("PHASE_3_VIDEO_LAB_ENABLED")
    if value is None:
        return True
    normalized = value.strip().lower()
    if normalized in {"0", "false", "no"}:
        return False
    if normalized in {"1", "true", "yes"}:
        return True
    return True


def _normalize_payload(raw: Any) -> Dict[str, Any]:
    if raw is None:
        return {}
    if isinstance(raw, bytes):
        return json.loads(raw.decode("utf-8", errors="replace"))
    if isinstance(raw, str):
        return json.loads(raw)
    if isinstance(raw, dict):
        return raw
    raise ValueError("Clip job payload must be JSON text/bytes or dict")


def _coerce_number(value: Any) -> Optional[float]:
    if value is None:
        return None
    if isinstance(value, (int, float)):
        return float(value)
    try:
        return float(str(value))
    except (TypeError, ValueError):
        return None


def _resolve_project_path(value: str) -> Path:
    """
    Resolve either an absolute path or a project-relative path.
    """
    candidate = Path(value)
    if candidate.is_absolute():
        return candidate
    # treat "/videos/..." etc as project-relative
    return (BASE_DIR / value.lstrip("/\\")).resolve()


def _sanitize_filename(name: str) -> str:
    allowed = []
    for char in name:
        if char.isalnum() or char in {" ", "-", "_", "."}:
            allowed.append(char)
        else:
            allowed.append("_")
    cleaned = "".join(allowed).strip(" _-.")
    return cleaned[:120] or "clip"


def _safe_output_path(path: Path) -> Path:
    """
    Prevent accidental writes outside CLIP_OUTPUT_DIR unless explicitly allowed.
    """
    resolved = path.resolve()
    if ALLOW_OUTPUT_OVERRIDE:
        return resolved

    base = CLIP_OUTPUT_DIR.resolve()
    try:
        resolved.relative_to(base)
    except ValueError:
        raise ValueError(f"Output path escapes CLIP_OUTPUT_DIR: {resolved}")
    return resolved


def _build_output_path(job: ClipJob, payload: Dict[str, Any]) -> Path:
    override = payload.get("output_path") or payload.get("output") or payload.get("clip_path")
    if override:
        out = _resolve_project_path(str(override))
        return _safe_output_path(out)

    match_id = job.get("match_id") or payload.get("match_id")
    event_id = job.get("event_id") or payload.get("event_id")
    clip_name = (
        payload.get("clip_name")
        or payload.get("name")
        or payload.get("label")
        or str(job.get("clip_id") or job.get("id") or "clip")
    )

    folder = CLIP_OUTPUT_DIR
    if match_id is not None:
        folder = folder / f"match_{int(match_id)}"
    if event_id is not None:
        folder = folder / f"event_{int(event_id)}"

    filename = _sanitize_filename(str(clip_name))
    if not filename.lower().endswith(".mp4"):
        filename = f"{filename}.mp4"

    return (folder / filename).resolve()


def _get_source_path(job: ClipJob, payload: Dict[str, Any]) -> Path:
    candidate = (
        payload.get("source_path")
        or payload.get("video_path")
        or payload.get("match_video_path")
        or job.get("source_path")
    )
    if not candidate:
        raise ValueError("Clip job does not include a source video path")

    source = _resolve_project_path(str(candidate))
    return source


def _extract_timing(job: ClipJob, payload: Dict[str, Any]) -> Tuple[float, float]:
    """
    Returns (start_seconds, duration_seconds).
    """
    start = _coerce_number(
        job.get("start_second")
        or job.get("match_second")
        or payload.get("start_second")
        or payload.get("start")
    )
    end = _coerce_number(job.get("end_second") or payload.get("end_second") or payload.get("end"))
    duration = _coerce_number(job.get("duration_seconds") or payload.get("duration_seconds"))

    if duration is None and start is not None and end is not None:
        duration = end - start

    if start is None or duration is None:
        raise ValueError("Clip job is missing start and/or duration")

    if duration <= 0:
        raise ValueError("Clip duration must be > 0 seconds")

    # sanity cap (6 hours) to prevent runaway values during testing
    if duration > 6 * 60 * 60:
        raise ValueError("Clip duration is unreasonably large")

    return float(start), float(duration)


def _get_clip_id(job: ClipJob, payload: Dict[str, Any]) -> Optional[int]:
    clip_id = job.get("clip_id") or payload.get("clip_id")
    if clip_id is None:
        return None
    return int(clip_id)


def _ensure_dirs() -> None:
    CLIP_OUTPUT_DIR.mkdir(parents=True, exist_ok=True)


def _startup_log() -> None:
    info("Clip worker starting...")
    info(f"BASE_DIR: {BASE_DIR}")
    info(f"VIDEO_DIR exists: {VIDEO_DIR.exists()}")
    info(f"CLIP_OUTPUT_DIR exists: {CLIP_OUTPUT_DIR.exists()}")
    info(f"POLL_INTERVAL: {POLL_INTERVAL}s")
    info(f"DRY_RUN: {dry_run_enabled()}")
    info(f"PHASE_3 enabled env: {os.environ.get('PHASE_3_VIDEO_LAB_ENABLED', '(default true)')}")
    info(f"ALLOW_OUTPUT_OVERRIDE: {ALLOW_OUTPUT_OVERRIDE}")


def run() -> None:
    _ensure_dirs()
    _startup_log()

    while True:
        if not phase3_is_enabled():
            time.sleep(PHASE_DISABLED_SLEEP)
            continue

        job: Optional[ClipJob] = None

        # Fetch + mark processing in a tight transaction.
        with get_connection() as conn:
            job = fetch_next_job(conn)
            if not job:
                conn.rollback()
            else:
                mark_job_processing(conn, job["id"])
                conn.commit()

        if not job:
            time.sleep(POLL_INTERVAL)
            continue

        job_id = job["id"]

        try:
            payload = _normalize_payload(job.get("payload"))
            start_seconds, duration_seconds = _extract_timing(job, payload)
            source = _get_source_path(job, payload)
            output = _build_output_path(job, payload)
            clip_id = _get_clip_id(job, payload)

            if not source.exists():
                raise FileNotFoundError(f"Source video not found: {source}")

            output = _safe_output_path(output)
            output.parent.mkdir(parents=True, exist_ok=True)

            if dry_run_enabled():
                info(f"Job {job_id}: DRY RUN (skipping ffmpeg) -> {output.name}")
                note = "dry_run: ffmpeg skipped"
            else:
                info(
                    f"Job {job_id}: generating clip "
                    f"({start_seconds:.2f}s -> {start_seconds + duration_seconds:.2f}s) "
                    f"from {source.name} => {output}"
                )
                generate_clip(source, start_seconds, duration_seconds, output)
                note = None

            with get_connection() as conn:
                # Let jobs.py decide how to write clip_path/status/etc.
                mark_job_completed(conn, job_id, clip_id, note=note)
                conn.commit()

            info(f"Job {job_id}: completed{' (dry run)' if dry_run_enabled() else ''}")

        except Exception as exc:
            error(f"Job {job_id}: failed", exc)
            with get_connection() as conn:
                mark_job_failed(conn, job_id, str(exc))
                conn.commit()
            time.sleep(1)


def main() -> None:
    run()


if __name__ == "__main__":
    main()
