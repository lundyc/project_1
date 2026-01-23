from __future__ import annotations

from contextlib import contextmanager

import pymysql
from pymysql.connections import Connection

from .config import DB_CONFIG


@contextmanager
def get_connection() -> Connection:
    """
    Provide a context-managed MariaDB/MySQL connection with autocommit disabled.
    """
    cfg = {key: value for key, value in DB_CONFIG.items() if value is not None}
    connection = pymysql.connect(**cfg)
    connection.autocommit(False)
    try:
        yield connection
    finally:
        connection.close()
