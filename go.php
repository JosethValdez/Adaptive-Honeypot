<?php
// Core router: logs every click, triggers page generation, then hands off to the loading poller.

// Files that must never be overwritten by the LLM generator.
define('PROTECTED_FILES', ['go.php', 'loading.php', 'gen_loading.php', 'trap.php']);

function safeBaseName(string $s): string {
    $s = str_replace("\\", "/", $s);
    $s = basename($s);
    $s = preg_replace('/[^A-Za-z0-9._-]/', '', $s);
    if ($s === '') $s = 'page.php';
    if (!preg_match('/\.php$/i', $s)) $s .= '.php';
    return $s;
}

function safeLabel(string $s): string {
    $s = strip_tags($s);
    $s = preg_replace('/[\x00-\x1F\x7F]/', '', $s);
    return substr(trim($s), 0, 80);
}

function logEvent(array $data): void {
    $line = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents(__DIR__ . '/clicks.log', $line, FILE_APPEND | LOCK_EX);
}

$ip     = $_SERVER['REMOTE_ADDR']    ?? '';
$ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ref    = $_SERVER['HTTP_REFERER']   ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI']    ?? '';
$ts     = date('Y-m-d H:i:s');

$p_raw     = (string)($_GET['p']     ?? '');
$label_raw = (string)($_GET['label'] ?? '');

$phpFile = safeBaseName($p_raw);
$label   = safeLabel($label_raw);

// Redirect protected-file requests to index so the LLM never overwrites them.
if (in_array(strtolower($phpFile), PROTECTED_FILES, true)) {
    $phpFile = 'index.php';
    $label   = $label ?: 'Server Links';
}

$base      = preg_replace('/\.php$/i', '', $phpFile);
$readyFlag = __DIR__ . '/' . $base . '.ready';
$pageFile  = __DIR__ . '/' . $phpFile;

logEvent([
    'ts' => $ts, 'type' => 'CLICK',
    'ip' => $ip, 'ua' => $ua, 'referer' => $ref,
    'method' => $method, 'uri' => $uri,
    'p_raw' => $p_raw, 'label_raw' => $label_raw,
]);

if (!file_exists($readyFlag)) {
    $py  = str_replace('/', '\\', __DIR__) . '\\page_agent.py';
    $cmd = 'cmd /c start "" /B py -3 "' . $py . '" "'
         . addslashes($phpFile) . '" "'
         . addslashes($label)   . '" > NUL 2>&1';
    shell_exec($cmd);
    logEvent([
        'ts' => $ts, 'type' => 'GEN',
        'ip' => $ip, 'ua' => $ua, 'referer' => $ref,
        'method' => $method, 'uri' => $uri,
        'cmd' => $cmd, 'target' => $phpFile, 'label' => $label,
    ]);
}

header('Location: gen_loading.php?p=' . rawurlencode($phpFile), true, 302);
exit;
