"""
linkfix.py — sanitize <a> tags in LLM-generated pages.

Every link must route through go.php in the form:
    <a href="go.php?p=FILENAME.php&label=LABEL">LINK TEXT</a>

The 1.5B model produces inconsistent URLs: spaces in labels, '?p ' instead
of '?p=', literal <?php ?> blocks, direct .php links, etc. This module
walks every <a ...>...</a>, derives a clean (filename, label, text),
and rewrites the tag. External links are dropped (text preserved).

Public API:
    fix(html: str) -> str
"""
import re

# Match a full <a ...>...</a> tag. Non-greedy on attrs and inner.
_ANCHOR_RE = re.compile(
    r"<a\b([^>]*?)>(.*?)</a\s*>",
    re.IGNORECASE | re.DOTALL,
)

# Capture the value of an href attribute (quoted or unquoted).
_HREF_RE = re.compile(
    r"""href\s*=\s*(?:"([^"]*)"|'([^']*)'|(\S+))""",
    re.IGNORECASE,
)

# Find a plausible *.php filename anywhere in a string.
_PHP_FILENAME_RE = re.compile(r"([A-Za-z0-9_-]+\.php)", re.IGNORECASE)

# Detect external schemes that should never appear in a generated page.
_EXTERNAL_RE = re.compile(r"^\s*(?:https?:|ftp:|mailto:|//)", re.IGNORECASE)

# Stray <?php ... ?> blocks inside generated HTML break the anchor parser
# (the '?>' closes the <a> tag prematurely) and have no legitimate use in
# our HTML-only output. Strip them before anchor processing.
_PHP_BLOCK_RE = re.compile(r"<\?php.*?\?>", re.IGNORECASE | re.DOTALL)


def _strip_tags(s: str) -> str:
    s = re.sub(r"<[^>]*>", "", s)
    return re.sub(r"\s+", " ", s).strip()


def _safe_label(text: str) -> str:
    """Alphanumeric + underscore only. Spaces become underscores."""
    text = re.sub(r"[^A-Za-z0-9 ]+", "", text)
    text = text.strip().replace(" ", "_")
    text = re.sub(r"_+", "_", text).strip("_")
    return text or "Link"


def _safe_filename(name: str) -> str:
    name = re.sub(r"[^A-Za-z0-9._-]", "", name)
    if not name:
        return "page.php"
    if not name.lower().endswith(".php"):
        name += ".php"
    return name


def _slug_from_text(text: str) -> str:
    slug = re.sub(r"[^A-Za-z0-9]+", "_", text).strip("_").lower()
    return f"{slug}.php" if slug else "page.php"


def _find_target_filename(*sources: str):
    """First *.php filename across sources that isn't go.php (the router)."""
    for s in sources:
        for m in _PHP_FILENAME_RE.finditer(s):
            name = m.group(1)
            if name.lower() != "go.php":
                return name
    return None


def _fix_anchor(match: "re.Match[str]") -> str:
    attrs = match.group(1) or ""
    inner = match.group(2) or ""
    text = _strip_tags(inner) or "Link"

    href_m = _HREF_RE.search(attrs)
    href = ""
    if href_m:
        href = href_m.group(1) or href_m.group(2) or href_m.group(3) or ""

    if _EXTERNAL_RE.match(href):
        return text  # drop the anchor, preserve link text

    fname = _find_target_filename(href, attrs)
    filename = _safe_filename(fname) if fname else _slug_from_text(text)
    label = _safe_label(text)

    return f'<a href="go.php?p={filename}&label={label}">{text}</a>'


def fix(html: str) -> str:
    """Rewrite every <a> tag to a well-formed go.php link."""
    html = _PHP_BLOCK_RE.sub("", html)
    return _ANCHOR_RE.sub(_fix_anchor, html)


if __name__ == "__main__":
    samples = [
        '<a href="go.php?p=foo.php&label=Foo_Bar">Foo Bar</a>',
        '<a href="go.php?p=cpu_usage.php&label=CPU Usage">CPU Usage</a>',
        '<a href="go.php?p disk_space.php&label=Disk Space">Disk Space</a>',
        '<a href="go.php?p=foo.php&label=<?php echo urlencode(\'Foo\'); ?>">Foo</a>',
        '<a href="go.php?p=baz.php&label>Baz Quux</a>',
        '<a href="contact.php">Contact</a>',
        '<a href="http://evil.com/">Evil</a>',
        '<a href="">Bare</a>',
    ]
    for s in samples:
        print(s)
        print("  ->", fix(s))
        print()
