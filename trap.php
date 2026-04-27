<?php
// Logging sink for all honeypot forms (login, search, upload, etc.)

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

$params = array_merge($_GET, $_POST);
$action = preg_replace('/[^a-z0-9_-]/i', '', (string)($params['action'] ?? 'unknown'));

// Sanitise captured field values for logging — keep them readable, strip control chars.
$fields = [];
foreach ($params as $k => $v) {
    $fields[substr((string)$k, 0, 40)] = substr(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', (string)$v), 0, 200);
}

logEvent([
    'ts' => $ts, 'type' => 'FORM',
    'ip' => $ip, 'ua' => $ua, 'referer' => $ref,
    'method' => $method, 'uri' => $uri,
    'action' => $action,
    'fields' => $fields,
]);

// Choose a contextual fake response.
if ($action === 'login') {
    $title   = 'Login Failed';
    $message = 'Invalid username or password. Please try again.';
} elseif ($action === 'search') {
    $q       = htmlspecialchars((string)($params['q'] ?? ''), ENT_QUOTES, 'UTF-8');
    $title   = 'Search Results';
    $message = $q !== '' ? "No results found for: <b>$q</b>." : 'Please enter a search term.';
} elseif ($action === 'upload') {
    $title   = 'Upload Error';
    $message = 'Upload failed. Insufficient permissions or unsupported file type.';
} else {
    $title   = 'Error';
    $message = 'An error occurred processing your request. Please try again.';
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> - srvweb01</title>
</head>
<body bgcolor="#FFFFFF">
<center>
<h2><font color="#CC0000"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></font></h2>
<hr width="60%">
<p><?php echo $message; ?></p>
<p>
  <a href="javascript:history.back()">Go Back</a>
  &nbsp;|&nbsp;
  <a href="go.php?p=index.php&label=<?php echo urlencode('Server Links'); ?>">Home</a>
</p>
</center>
</body>
</html>
