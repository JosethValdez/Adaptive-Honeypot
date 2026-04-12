<?php
function safeBaseName($s) {
  $s = str_replace("\\", "/", (string)$s);
  $s = basename($s);
  $s = preg_replace('/[^A-Za-z0-9._-]/', '', $s);
  if ($s === '') $s = 'page.php';
  if (!preg_match('/\.php$/i', $s)) $s .= '.php';
  return $s;
}

$p = isset($_GET['p']) ? $_GET['p'] : 'page.php';
$phpFile = safeBaseName($p);
$base = preg_replace('/\.php$/i', '', $phpFile);

$readyFlag = __DIR__ . "/" . $base . ".ready";

if (file_exists($readyFlag)) {
  header("Location: " . $phpFile, true, 302);
  exit;
}

header("Refresh: 2; url=gen_loading.php?p=" . rawurlencode($phpFile));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Loading…</title>
  <style>
    html, body { height: 100%; }
    body {
      margin: 0;
      background: #ffffff;
      color: #000;
      font: 16px/1.4 "Times New Roman", Times, serif;
      overflow: hidden;
    }
    .overlay {
      position: fixed;
      inset: 0;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,0.45);
    }
    .loading-box {
      width: min(520px, calc(100% - 40px));
      border: 1px solid #bbb;
      background: #fff;
      padding: 14px 16px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.12);
      font-family: Arial, Helvetica, sans-serif;
    }
    .loading-row { display: flex; align-items: center; gap: 10px; }
    .spinner {
      width: 16px;
      height: 16px;
      border: 2px solid #c8c8c8;
      border-top: 2px solid #444;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      flex: 0 0 auto;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .loading-title { font-size: 14px; margin: 0; color: #111; }
    .loading-sub { margin: 8px 0 0; font-size: 12px; color: #444; }
  </style>
</head>
<body>
  <div class="overlay" role="status" aria-live="polite">
    <div class="loading-box">
      <div class="loading-row">
        <div class="spinner" aria-hidden="true"></div>
        <p class="loading-title"><strong>Loading…</strong></p>
      </div>
      <p class="loading-sub">Generating <code><?php echo htmlspecialchars($phpFile, ENT_QUOTES, 'UTF-8'); ?></code></p>
      <p class="loading-sub">Checking again in about 2 seconds…</p>
    </div>
  </div>
</body>
</html>