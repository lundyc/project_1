from __future__ import annotations

from typing import Any, Dict, Optional, TypedDict

import pymysql

from pymysql.cursors import DictCursor
from pymysql.connections import Connection

JOB_STATUS_PENDING = "pending"
JOB_STATUS_PROCESSING = "processing"
JOB_STATUS_COMPLETED = "completed"
JOB_STATUS_FAILED = "failed"

_NOTE_COLUMN_CHECKED: Optional[bool] = None


def _supports_note_column(conn: Connection) -> bool:
    global _NOTE_COLUMN_CHECKED
    if _NOTE_COLUMN_CHECKED is not None:
        return _NOTE_COLUMN_CHECKED
    try:
        with conn.cursor() as cursor:
            cursor.execute("SHOW COLUMNS FROM clip_jobs LIKE 'note'")
            _NOTE_COLUMN_CHECKED = cursor.fetchone() is not None
    except pymysql.err.ProgrammingError:
        _NOTE_COLUMN_CHECKED = False
    return _NOTE_COLUMN_CHECKED

MAX_ERROR_MESSAGE = 1024


class ClipJob(TypedDict, total=False):
    id: int
    match_id: int
    event_id: int
    clip_id: Optional[int]
    payload: Dict[str, Any]
    status: str
    attempt: Optional[int]
    error_message: Optional[str]


def fetch_next_job(conn: Connection) -> Optional[ClipJob]:
    """
    Lock and return the next pending clip job without committing so callers control the transaction.
    """
    with conn.cursor(DictCursor) as cursor:
        cursor.execute(
            """
            SELECT *
            FROM clip_jobs
            WHERE status = %s
            ORDER BY id ASC
            LIMIT 1
            FOR UPDATE SKIP LOCKED
            """,
            (JOB_STATUS_PENDING,),
        )
        job = cursor.fetchone()
    return job


def mark_job_processing(conn: Connection, job_id: int) -> None:
    """
    Transition a job into the processing state.
    """
    with conn.cursor() as cursor:
        cursor.execute(
            "UPDATE clip_jobs SET status = %s WHERE id = %s",
            (JOB_STATUS_PROCESSING, job_id),
        )


def mark_job_completed(conn: Connection, job_id: int, clip_id: Optional[int] = None, note: Optional[str] = None) -> None:
    """
    Finalise a job as completed and optionally link it to a clip.
    """
    assignments = ["status = %s"]
    params: list[Any] = [JOB_STATUS_COMPLETED]
    if clip_id is not None:
        assignments.append("clip_id = %s")
        params.append(clip_id)
    assignments.append("error_message = NULL")
    if note and _supports_note_column(conn):
        assignments.append("note = %s")
        params.append(note)
    params.append(job_id)

    with conn.cursor() as cursor:
        cursor.execute(
            f"UPDATE clip_jobs SET {', '.join(assignments)} WHERE id = %s",
            tuple(params),
        )


def mark_job_failed(conn: Connection, job_id: int, error_message: str) -> None:
    """
    Mark a job as failed and capture a user-friendly error message.
    """
    truncated_error = error_message[:MAX_ERROR_MESSAGE]
    with conn.cursor() as cursor:
        cursor.execute(
            "UPDATE clip_jobs SET status = %s, error_message = %s WHERE id = %s",
            (JOB_STATUS_FAILED, truncated_error, job_id),
        )
