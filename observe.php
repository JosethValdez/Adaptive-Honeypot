<?php
// observe.php: quick live view of clicks.log + Cowrie JSON
// Adjust paths if needed:
$clicks_path = __DIR__ . "/clicks.log";
$cowrie_path = __DIR__ . "/cowrie-logs/cowrie.json";

$limit = isset($_GET['n']) ? max(10, min(500, (int)$_GET['n'])) : 120;

function tail_lines($path, $maxLines) {
  if (!file_exists($path)) return [];
  $lines = @file($path, FILE_IGNORE_NEW_LINES);
  if (!is_array($lines)) return [];
  $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ""));
  if (count($lines) <= $maxLines) return $lines;
  return array_slice($lines, -$maxLines);
}

function parse_json_lines($lines) {
  $out = [];
  foreach ($lines as $l) {
    $j = json_decode($l, true);
    if (is_array($j)) $out[] = $j;
  }
  return $out;
}

$click_events = parse_json_lines(tail_lines($clicks_path, $limit));
$cowrie_events = parse_json_lines(tail_lines($cowrie_path, $limit));

// Normalize into a single table view
$rows = [];

foreach ($click_events as $e) {
  $rows[] = [
    "src" => "web",
    "ts" => $e["ts"] ?? "",
    "type" => $e["type"] ?? "",
    "ip" => $e["ip"] ?? "",
    "uri" => $e["uri"] ?? "",
    "ua" => $e["ua"] ?? "",
    "extra" => "",
  ];
}

foreach ($cowrie_events as $e) {
  // Cowrie fields vary by event type; these are common:
  $ts = $e["timestamp"] ?? ($e["time"] ?? "");
  $etype = $e["eventid"] ?? ($e["event"] ?? "cowrie");
  $ip = $e["src_ip"] ?? ($e["srcip"] ?? "");
  $uri = $e["input"] ?? ($e["message"] ?? "");
  $rows[] = [
    "src" => "cowrie",
    "ts" => (string)$ts,
    "type" => (string)$etype,
    "ip" => (string)$ip,
    "uri" => (string)$uri,
    "ua" => "",
    "extra" => "",
  ];
}

// Sort by timestamp string (best-effort). If formats differ, it’s still “good enough” for a live tail.
usort($rows, function($a, $b) {
  return strcmp($a["ts"], $b["ts"]);
});

// Keep last N after merge
if (count($rows) > $limit) $rows = array_slice($rows, -$limit);

header("Content-Type: text/html; charset=utf-8");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta http-equiv="refresh" content="2" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Observe</title>
  <style>
    body { font-family: Arial, sans-serif; background: #fff; color:#111; margin: 16px; }
    h1 { margin: 0 0 10px; font-size: 18px; }
    .meta { font-size: 12px; color:#444; margin-bottom: 10px; }
    table { border-collapse: collapse; width: 100%; font-size: 12px; }
    th, td { border: 1px solid #ddd; padding: 6px 8px; vertical-align: top; }
    th { background: #f4f4f4; text-align: left; }
    .src-web { background:#fff; }
    .src-cowrie { background:#fbfbff; }
    .type-TRAP { background:#fff3f3; }
    code { white-space: pre-wrap; }
  </style>
</head>
<body>
  <h1>Live Observe</h1>
  <div class="meta">
    Now: <code><?php echo htmlspecialchars(date("Y-m-d H:i:s")); ?></code> |
    Showing last <code><?php echo (int)$limit; ?></code> merged rows |
    <a href="?n=120">n=120</a> |
    <a href="?n=300">n=300</a>
    <br />
    clicks.log: <code><?php echo htmlspecialchars($clicks_path); ?></code> |
    cowrie.json: <code><?php echo htmlspecialchars($cowrie_path); ?></code>
  </div>

  <table>
    <thead>
      <tr>
        <th>src</th>
        <th>ts</th>
        <th>type</th>
        <th>ip</th>
        <th>uri / input</th>
        <th>ua</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): 
        $cls = "src-" . ($r["src"] === "cowrie" ? "cowrie" : "web");
        if (($r["type"] ?? "") === "TRAP") $cls .= " type-TRAP";
      ?>
      <tr class="<?php echo $cls; ?>">
        <td><?php echo htmlspecialchars($r["src"]); ?></td>
        <td><?php echo htmlspecialchars($r["ts"]); ?></td>
        <td><?php echo htmlspecialchars($r["type"]); ?></td>
        <td><?php echo htmlspecialchars($r["ip"]); ?></td>
        <td><code><?php echo htmlspecialchars($r["uri"]); ?></code></td>
        <td><code><?php echo htmlspecialchars($r["ua"]); ?></code></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>