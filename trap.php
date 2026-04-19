<?php
// trap.php: logs scanner/probe requests without generating AI pages

$clickLog = __DIR__ . "/clicks.log";

function client_ip() {
  $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
  if ($xff) {
    $parts = explode(',', $xff);
    $ip = trim($parts[0]);
    if ($ip !== '') return $ip;
  }
  return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

$event = [
  "ts" => date("Y-m-d H:i:s"),
  "type" => "TRAP",
  "ip" => client_ip(),
  "ua" => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
  "referer" => (string)($_SERVER['HTTP_REFERER'] ?? ''),
  "method" => (string)($_SERVER['REQUEST_METHOD'] ?? ''),
  "uri" => (string)($_SERVER['REQUEST_URI'] ?? ''),
];

@file_put_contents($clickLog, json_encode($event, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);

// Look like a normal "not found"
http_response_code(404);
header("Content-Type: text/html; charset=utf-8");
?>
<!doctype html>
<html>
<head><title>404 Not Found</title></head>
<body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
<hr>
<address>Apache Server</address>
</body>
</html>