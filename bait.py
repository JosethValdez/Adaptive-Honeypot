"""
Post-processing bait injection for generated honeypot pages.

Deterministic: seeded by filename so the same page always gets the same bait
even after regeneration.  All forms submit to trap.php.
"""
import hashlib
import random
import re

# --- credential / debug bait pools ---

_CRED_COMMENTS = [
    "<!-- TODO: update admin creds before cutover -- webmaster:Web@2001 -->",
    "<!-- DB config: host=192.168.10.50 user=webapp pass=Webapp#99 db=corpintra -->",
    "<!-- legacy account: admin / Corp@dmin2000 -- disable after migration -->",
    "<!-- FTP access: ftpsrv / Ftp!Srv2003 -- contact IT dept -->",
    "<!-- backup cron uses: backup / B@ckup!01 -- read-only account -->",
    "<!-- DEV ONLY: remove before prod -- test login admin:admin123 -->",
    "<!-- mysql defaults still set: root@localhost / root -- dev env -->",
    "<!-- sysadmin temporary pass: S@sAdmin2002 -- force change on next login -->",
]

_DEBUG_SNIPPETS = [
    '<?php /* DEBUG -- remove: $db_pass = "corp_db_2001"; */ ?>',
    '<?php /* TEMP: define("ADMIN_SECRET", "a3f9c2d1b4e8"); remove me */ ?>',
    '<!-- DEBUG: SESSION_SECRET=7f3a9b2c4d1e (do not commit) -->',
    '<!-- env dump: APP_KEY=base64:Tz3a9b2cX1eQ= DB_PASS=intra_2001 -->',
    '<?php /* TODO: $config["api_key"] = "sk-corp-4f8a2b9c1d3e"; */ ?>',
    '<!-- leftover: SMTP_USER=mailrelay SMTP_PASS=relay!2003 host=mail.corp.local -->',
]

# --- admin panel hint pools ---

_ADMIN_LINK_SETS = [
    [
        ('Admin Panel',    'admin.php',          'Administration'),
        ('phpMyAdmin',     'phpmyadmin.php',      'Database Admin'),
        ('Backup Manager', 'backup_manager.php',  'Backup Manager'),
    ],
    [
        ('Server Admin',   'sysadmin.php',        'System Administration'),
        ('File Manager',   'filemanager.php',     'File Manager'),
        ('Log Viewer',     'logs.php',            'Log Viewer'),
    ],
    [
        ('Control Panel',  'cpanel.php',          'Control Panel'),
        ('DB Admin',       'dbadmin.php',         'Database Administration'),
        ('User Manager',   'users.php',           'User Management'),
    ],
    [
        ('Admin Tools',    'admin_tools.php',     'Admin Tools'),
        ('Config Editor',  'config_editor.php',   'Configuration Editor'),
        ('Access Logs',    'access_logs.php',     'Access Logs'),
    ],
]

# --- fake file listing pools ---

_FILE_LISTING_SETS = [
    [
        ('.env',              'env_config.php',     'Environment Config',    '1.2 KB'),
        ('backup_corp.sql',   'db_backup.php',      'Database Backup',       '2.1 MB'),
        ('httpd.conf.bak',    'config_backup.php',  'Config Backup',         '8.4 KB'),
        ('install.log',       'install_log.php',    'Install Log',           '34 KB'),
    ],
    [
        ('.env.production',   'env_prod.php',       'Production Environment','1.4 KB'),
        ('db_dump_2003.sql',  'db_dump.php',        'Database Dump',         '5.8 MB'),
        ('config.bak',        'config_bak.php',     'App Config Backup',     '3.2 KB'),
        ('access.log.2003',   'access_log.php',     'Access Log',            '128 KB'),
    ],
    [
        ('.htpasswd',         'htpasswd.php',       'Password File',         '512 B'),
        ('site_backup.tar.gz','site_backup.php',    'Site Backup Archive',   '18 MB'),
        ('wp-config.php.bak', 'wp_config_bak.php',  'WP Config Backup',      '6.1 KB'),
        ('users_export.csv',  'users_export.php',   'User Export',           '44 KB'),
    ],
    [
        ('.env.local',        'env_local.php',      'Local Environment',     '980 B'),
        ('full_dump.sql',     'full_dump.php',      'Full Database Dump',    '12 MB'),
        ('php.ini.bak',       'phpini_bak.php',     'PHP Config Backup',     '72 KB'),
        ('cron.log',          'cron_log.php',       'Cron Log',              '210 KB'),
    ],
]

# --- form templates (all submit to trap.php) ---

_LOGIN_FORM = """\
<hr>
<center><b>Account Login</b></center>
<form method="POST" action="trap.php">
<input type="hidden" name="action" value="login">
<table align="center" cellpadding="3" border="0">
<tr><td>Username:</td><td><input type="text" name="username" size="20"></td></tr>
<tr><td>Password:</td><td><input type="password" name="password" size="20"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" value="Login"></td></tr>
</table>
</form>"""

_SEARCH_FORM = """\
<hr>
<form method="GET" action="trap.php">
<input type="hidden" name="action" value="search">
<b>Search:</b>&nbsp;<input type="text" name="q" size="30">&nbsp;<input type="submit" value="Search">
</form>"""

_UPLOAD_FORM = """\
<hr>
<center><b>Upload File</b></center>
<form method="POST" action="trap.php" enctype="multipart/form-data">
<input type="hidden" name="action" value="upload">
<input type="file" name="file">&nbsp;&nbsp;<input type="submit" value="Upload">
</form>"""

_FORMS = [_LOGIN_FORM, _SEARCH_FORM, _UPLOAD_FORM]


def _rng(filename: str) -> random.Random:
    digest = hashlib.md5(filename.encode()).hexdigest()
    return random.Random(int(digest, 16))


def _build_go_link(filename: str, label: str, text: str) -> str:
    return (
        f'<a href="go.php?p={filename}'
        f'&label=<?php echo urlencode(\'{label}\'); ?>">'
        f'{text}</a>'
    )


def inject_bait(html: str, filename: str) -> str:
    """Inject honeypot bait into generated page HTML.

    Called after LLM output is cleaned.  Returns modified HTML.
    """
    rng = _rng(filename)

    # 1. Credential comment + debug snippet — injected right after <body ...>
    cred    = rng.choice(_CRED_COMMENTS)
    debug   = rng.choice(_DEBUG_SNIPPETS)
    cred_block = f"\n{cred}\n{debug}\n"

    body_tag = re.search(r'<body[^>]*>', html, re.IGNORECASE)
    if body_tag:
        pos = body_tag.end()
        html = html[:pos] + cred_block + html[pos:]
    else:
        html = cred_block + html

    # 2. Admin hint links + optional file listing + a form — appended before </body>
    admin_set  = rng.choice(_ADMIN_LINK_SETS)
    admin_html = " | ".join(
        _build_go_link(fn, lbl, txt) for txt, fn, lbl in admin_set
    )
    admin_block = (
        '\n<hr>\n'
        '<p align="center"><small><font color="#999999">'
        f'Internal Tools: {admin_html}'
        '</font></small></p>\n'
    )

    file_block = ""
    if rng.random() < 0.65:
        files = rng.choice(_FILE_LISTING_SETS)
        items = "\n".join(
            f'<li>{_build_go_link(fn, lbl, name)} ({size})</li>'
            for name, fn, lbl, size in files
        )
        file_block = f'\n<hr>\n<p><b>Available Files:</b></p>\n<ul>\n{items}\n</ul>\n'

    form = rng.choice(_FORMS)

    tail = admin_block + file_block + form + "\n"

    close_body = re.search(r'</body>', html, re.IGNORECASE)
    if close_body:
        pos = close_body.start()
        html = html[:pos] + tail + html[pos:]
    else:
        html = html + tail

    return html
