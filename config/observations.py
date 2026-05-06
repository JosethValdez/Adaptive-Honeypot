"""
Observation store — single source of truth for what has been revealed.

Records are write-once for content: content_hash and content_snapshot
capture the exact state at the moment of first observation and are never
overwritten. Subsequent observations only increment observe_count and
update last_seen.

Artifact types:
  'web'    — a PHP page served over HTTP (keyed by filename, e.g. "index.php")
  'cowrie' — a honeyfs file read during an SSH session (e.g. "etc/passwd")
"""
import hashlib
import sqlite3
from contextlib import contextmanager
from datetime import datetime, timezone
from typing import Dict, List, Optional
import os

# DB lives at the project root so both Python and PHP can reach it.
_HERE = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.join(_HERE, "..", "observations.db")
DB_PATH = os.path.normpath(DB_PATH)

_SCHEMA = """
CREATE TABLE IF NOT EXISTS artifacts (
    artifact_path    TEXT PRIMARY KEY,
    artifact_type    TEXT NOT NULL,
    first_seen       TEXT NOT NULL,
    last_seen        TEXT NOT NULL,
    observe_count    INTEGER NOT NULL DEFAULT 1,
    content_hash     TEXT NOT NULL,
    content_snapshot TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_artifact_type ON artifacts (artifact_type);
"""


# ---------------------------------------------------------------------------
# Internal helpers
# ---------------------------------------------------------------------------

def _now() -> str:
    return datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")


@contextmanager
def _conn():
    con = sqlite3.connect(DB_PATH)
    con.row_factory = sqlite3.Row
    try:
        yield con
        con.commit()
    finally:
        con.close()


def _sha256(content: str) -> str:
    return hashlib.sha256(content.encode("utf-8")).hexdigest()


# ---------------------------------------------------------------------------
# Public API
# ---------------------------------------------------------------------------

def init_db() -> None:
    """Create the DB and tables if they don't exist yet."""
    with _conn() as con:
        con.executescript(_SCHEMA)


def record(artifact_path: str, artifact_type: str, content: str) -> bool:
    """
    Record an observation of artifact_path with the given content.

    Returns True  — first observation; snapshot and hash were frozen.
    Returns False — already observed; snapshot preserved, count incremented.
    """
    init_db()
    now = _now()
    h = _sha256(content)
    with _conn() as con:
        existing = con.execute(
            "SELECT artifact_path FROM artifacts WHERE artifact_path = ?",
            (artifact_path,),
        ).fetchone()
        if existing is None:
            con.execute(
                """
                INSERT INTO artifacts
                    (artifact_path, artifact_type, first_seen, last_seen,
                     observe_count, content_hash, content_snapshot)
                VALUES (?, ?, ?, ?, 1, ?, ?)
                """,
                (artifact_path, artifact_type, now, now, h, content),
            )
            return True
        else:
            con.execute(
                """
                UPDATE artifacts
                   SET last_seen     = ?,
                       observe_count = observe_count + 1
                 WHERE artifact_path = ?
                """,
                (now, artifact_path),
            )
            return False


def get(artifact_path: str) -> Optional[Dict]:
    """
    Return the observation record for artifact_path, or None if not observed.

    Keys: artifact_path, artifact_type, first_seen, last_seen,
          observe_count, content_hash, content_snapshot.
    """
    init_db()
    with _conn() as con:
        row = con.execute(
            "SELECT * FROM artifacts WHERE artifact_path = ?",
            (artifact_path,),
        ).fetchone()
    return dict(row) if row else None


def is_observed(artifact_path: str) -> bool:
    """True if artifact_path has been observed at least once."""
    return get(artifact_path) is not None


def list_all(artifact_type: Optional[str] = None) -> List[Dict]:
    """
    Return all observations, optionally filtered by artifact_type,
    ordered by first_seen ascending.
    """
    init_db()
    with _conn() as con:
        if artifact_type is not None:
            rows = con.execute(
                "SELECT * FROM artifacts WHERE artifact_type = ? ORDER BY first_seen",
                (artifact_type,),
            ).fetchall()
        else:
            rows = con.execute(
                "SELECT * FROM artifacts ORDER BY first_seen"
            ).fetchall()
    return [dict(r) for r in rows]


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------

def _cli_init():
    init_db()
    print(f"OK  {DB_PATH}")


def _cli_list(artifact_type: Optional[str] = None):
    rows = list_all(artifact_type)
    if not rows:
        print("No observations recorded.")
        return
    print(f"{'TYPE':<8}  {'COUNT':>5}  {'FIRST SEEN':>20}  ARTIFACT")
    print("-" * 72)
    for r in rows:
        print(
            f"{r['artifact_type']:<8}  {r['observe_count']:>5}  "
            f"{r['first_seen']:>20}  {r['artifact_path']}"
        )


def _cli_show(artifact_path: str):
    r = get(artifact_path)
    if r is None:
        print(f"Not observed: {artifact_path}")
        return
    print(f"path:      {r['artifact_path']}")
    print(f"type:      {r['artifact_type']}")
    print(f"first:     {r['first_seen']}")
    print(f"last:      {r['last_seen']}")
    print(f"count:     {r['observe_count']}")
    print(f"hash:      {r['content_hash']}")
    print(f"snapshot ({len(r['content_snapshot'])} chars):")
    print("-" * 40)
    print(r["content_snapshot"])


if __name__ == "__main__":
    import sys

    usage = "Usage: python -m config.observations [init | list [web|cowrie] | show <path>]"
    args = sys.argv[1:]

    if not args or args[0] == "init":
        _cli_init()
    elif args[0] == "list":
        _cli_list(args[1] if len(args) > 1 else None)
    elif args[0] == "show" and len(args) == 2:
        _cli_show(args[1])
    else:
        print(usage)
        sys.exit(1)
