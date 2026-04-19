<?php
// go.php: router for AI-generated pages (Windows/XAMPP)

$clickLog = __DIR__ . "/clicks.log";

function client_ip() {
  // Prefer X-Forwarded-For if you are behind a reverse proxy (optional)
  $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
  if ($xff) {
    $parts = explode(',', $xff);
    $ip = trim($parts[0]);
    if ($ip !== '') return $ip;
  }
  return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function req_header($key) {
  $k = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
  return $_SERVER[$k] ?? '';
}

function log_click_event($type, $extra = []) {
  global $clickLog;

  $event = array_merge([
    "ts" => date("Y-m-d H:i:s"),
    "type" => $type,
    "ip" => client_ip(),
    "ua" => (string)(req_header('User-Agent')),
    "referer" => (string)($_SERVER['HTTP_REFERER'] ?? ''),
    "method" => (string)($_SERVER['REQUEST_METHOD'] ?? ''),
    "uri" => (string)($_SERVER['REQUEST_URI'] ?? ''),
  ], $extra);

  @file_put_contents($clickLog, json_encode($event, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
}

function safeBaseName($s) {
  $s = str_replace("\\", "/", (string)$s);
  $s = basename($s);
  $s = preg_replace('/[^A-Za-z0-9._-]/', '', $s);
  if ($s === '') return '';
  if (!preg_match('/\.php$/i', $s)) $s .= '.php';
  return $s;
}

$p_raw = $_GET['p'] ?? '';
$label_raw = $_GET['label'] ?? '';

log_click_event("CLICK", [
  "p_raw" => (string)$p_raw,
  "label_raw" => (string)$label_raw,
]);

$phpFile = safeBaseName($p_raw);
if ($phpFile === '') {
  http_response_code(400);
  header("Content-Type: text/plain; charset=utf-8");
  echo "Missing or invalid parameter: p\n";
  echo "URL: " . ($_SERVER["REQUEST_URI"] ?? "") . "\n";
  exit;
}

$base = preg_replace('/\.php$/i', '', $phpFile);
$phpPath = __DIR__ . DIRECTORY_SEPARATOR . $phpFile;
$readyFlag = __DIR__ . DIRECTORY_SEPARATOR . $base . ".ready";

$labelArg = trim((string)$label_raw);
if ($labelArg === '') $labelArg = $base;

if (file_exists($phpPath) && file_exists($readyFlag)) {
  log_click_event("CACHE_REDIRECT", ["target" => $phpFile]);
  header("Location: " . $phpFile, true, 302);
  exit;
}

$script = __DIR__ . DIRECTORY_SEPARATOR . "page_agent.py";

if (file_exists($readyFlag)) {
  @unlink($readyFlag);
}

$cmd =
  'cmd /c start "" /B py -3 ' .
  escapeshellarg($script) . ' ' .
  escapeshellarg($phpFile) . ' ' .
  escapeshellarg($labelArg) .
  ' > NUL 2>&1';

log_click_event("GEN", ["cmd" => $cmd, "target" => $phpFile, "label" => $labelArg]);
@exec($cmd);

header("Location: gen_loading.php?p=" . urlencode($phpFile), true, 302);
exit;