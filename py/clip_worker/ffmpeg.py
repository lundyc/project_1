from __future__ import annotations

from pathlib import Path
import subprocess


def generate_clip(
    source: Path | str,
    start_seconds: float,
    duration_seconds: float,
    output: Path | str,
) -> Path:
    """
    Extract a subclip from `source` into `output` without re-encoding.
    """
    source_path = Path(source)
    if not source_path.is_file():
        raise FileNotFoundError(f"Source video not found: {source_path}")

    output_path = Path(output)
    output_path.parent.mkdir(parents=True, exist_ok=True)

    command = [
        "ffmpeg",
        "-hide_banner",
        "-loglevel",
        "error",
        "-ss",
        str(start_seconds),
        "-i",
        str(source_path),
        "-t",
        str(duration_seconds),
        "-c",
        "copy",
        "-y",
        str(output_path),
    ]

    subprocess.run(command, check=True)
    return output_path
