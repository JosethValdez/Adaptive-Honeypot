<?php
// go.php: router for AI-generated pages (Windows/XAMPP)
// - Requires ?p=... (no silent default)
// - Logs every click so you can see what params are arriving

$clickLog = __DIR__ . "/clicks.log";
function log_click($msg) {
  global $clickLog;
  @file_put_contents($clickLog, "[" . date("Y-m-d H:i:s") . "] " . $msg . "\n", FILE_APPEND);
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

log_click("RAW url=" . ($_SERVER["REQUEST_URI"] ?? "") . " | p_raw=" . $p_raw . " | label_raw=" . $label_raw);

// Require p. If missing, don’t “default”, because that causes the wrong-page problem.
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

// Normalize label: browsers decode + into space when reading $_GET.
// That’s fine; we just pass it to python as plain text.
$labelArg = trim((string)$label_raw);
if ($labelArg === '') $labelArg = $base;

// If already generated, go straight there
if (file_exists($phpPath) && file_exists($readyFlag)) {
  log_click("HIT cache -> redirect " . $phpFile);
  header("Location: " . $phpFile, true, 302);
  exit;
}

// Trigger generation
$script = __DIR__ . DIRECTORY_SEPARATOR . "page_agent.py";

// Clear ready flag to avoid stale redirect
if (file_exists($readyFlag)) {
  @unlink($readyFlag);
}

$cmd =
  'cmd /c start "" /B py -3 ' .
  escapeshellarg($script) . ' ' .
  escapeshellarg($phpFile) . ' ' .
  escapeshellarg($labelArg) .
  ' > NUL 2>&1';

log_click("GEN cmd=" . $cmd);
@exec($cmd);

header("Location: gen_loading.php?p=" . urlencode($phpFile), true, 302);
exit;