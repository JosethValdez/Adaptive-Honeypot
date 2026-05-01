"""
bait.py — deterministic bait injector (v1).

Inserts two decoys into LLM-generated pages:
  1. A credential HTML comment immediately after <body>.
  2. An admin-tools <ul> footer immediately before </body>.

Determinism: random.Random(md5(filename)). Same filename, same bait —
returning attackers see consistent decoys.

Public API:
    inject(filename: str, html: str) -> str
"""
import hashlib
import random
import re
from urllib.parse import quote

from config import identity


# Service / app credentials that don't map to real OS users.
# OS users with usable ssh_password values are appended at runtime
# from identity.json — that's the bridge to Cowrie in Phase 2.
_SERVICE_CREDS = [
    ("dbadmin",    "Sp!ring2019"),
    ("svc_backup", "Backup#2018"),
    ("svnuser",    "svn1234"),
    ("ftpadmin",   "ftp_2017!"),
    ("oracle",     "Oracle9i"),
    ("monitor",    "M0n1tor#"),
]

_CRED_TEMPLATES = [
    "TODO({author}): rotate {user} pw before audit -- current: {user} / {pw}",
    "DEBUG: login failing for {user} -- creds in use: {user} / {pw}",
    "FIXME: hardcoded creds -- move to env. {user} / {pw}",
    "MEMO from {author}: password reset to '{pw}' for {user}, please update scripts",
    "NOTE: {svc} access -- {user} / {pw} (legacy, do not change)",
]

_SERVICE_LABELS = ["db", "backup", "svn", "ftp", "oracle", "monitor"]

_ADMIN_TOOLS = [
    ("logs_viewer.php",       "Logs Viewer"),
    ("db_console.php",        "DB Console"),
    ("backup_console.php",    "Backup Console"),
    ("system_health.php",     "System Health"),
    ("phpmyadmin_legacy.php", "phpMyAdmin (legacy)"),
    ("cron_status.php",       "Cron Status"),
    ("user_admin.php",        "User Admin"),
    ("sysmon.php",            "System Monitor"),
    ("mailq_viewer.php",      "Mail Queue"),
    ("disk_usage.php",        "Disk Usage"),
]


def _rng_for(filename: str) -> random.Random:
    return random.Random(hashlib.md5(filename.encode("utf-8")).hexdigest())


def _cred_pool() -> list[tuple[str, str]]:
    pool = list(_SERVICE_CREDS)
    for u in identity.get("users", []):
        pw = u.get("ssh_password", "")
        if pw and pw not in ("*", "!"):
            pool.append((u["username"], pw))
    return pool


def _pick_author(rng: random.Random) -> str:
    candidates = [
        u["username"] for u in identity.get("users", [])
        if u["username"] != "nobody"
    ]
    return rng.choice(candidates) if candidates else "webmaster"


def _credential_comment(rng: random.Random) -> str:
    user, pw = rng.choice(_cred_pool())
    body = rng.choice(_CRED_TEMPLATES).format(
        user=user,
        pw=pw,
        svc=rng.choice(_SERVICE_LABELS),
        author=_pick_author(rng),
    )
    return f"<!-- {body} -->"


def _admin_footer(rng: random.Random) -> str:
    n = rng.randint(3, 5)
    picks = rng.sample(_ADMIN_TOOLS, k=n)
    items = "\n".join(
        f'  <li><a href="go.php?p={fname}&label={quote(label)}">{label}</a></li>'
        for fname, label in picks
    )
    return (
        "\n<hr>\n"
        '<font size="-1" color="#666666">Admin Tools:</font>\n'
        "<ul>\n"
        f"{items}\n"
        "</ul>\n"
    )


_BODY_OPEN_RE  = re.compile(r"<body\b[^>]*>", re.IGNORECASE)
_BODY_CLOSE_RE = re.compile(r"</body\s*>",    re.IGNORECASE)


def inject(filename: str, html: str) -> str:
    """Return html with bait inserted. Deterministic per filename."""
    rng = _rng_for(filename)
    cred = _credential_comment(rng)
    footer = _admin_footer(rng)

    m = _BODY_OPEN_RE.search(html)
    if m:
        i = m.end()
        html = html[:i] + "\n" + cred + "\n" + html[i:]
    else:
        html = cred + "\n" + html

    m = _BODY_CLOSE_RE.search(html)
    if m:
        i = m.start()
        html = html[:i] + footer + html[i:]
    else:
        html = html + footer

    return html


if __name__ == "__main__":
    import sys
    fname = sys.argv[1] if len(sys.argv) > 1 else "demo.php"
    sample = (
        "<html>\n<body bgcolor=\"white\">\n"
        "<h1>Sample Page</h1>\n<p>Hello.</p>\n"
        "</body>\n</html>\n"
    )
    print(inject(fname, sample))
