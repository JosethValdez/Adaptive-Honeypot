<?php
$readyFlag = __DIR__ . "/index.ready";

if (file_exists($readyFlag)) {
  header("Location: index.php", true, 302);
  exit;
}

// Not ready yet: keep showing this same page and poll again soon.
header("Refresh: 2; url=loading.php");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Loading...</title>

  <style>
    /* --- "Typical Apache page" vibe (plain, serif-ish, lots of whitespace) --- */
    html, body { height: 100%; }
    body {
      margin: 0;
      background: #ffffff;
      color: #000;
      font: 16px/1.4 "Times New Roman", Times, serif;
      overflow: hidden; /* keeps it feeling like a fixed loading screen */
    }
    a { color: #00f; }
    hr { border: 0; border-top: 1px solid #bbb; margin: 18px 0; }

    .page {
      max-width: 920px;
      margin: 28px auto;
      padding: 0 18px 40px;
    }

    h1 {
      font-size: 26px;
      font-weight: bold;
      margin: 0 0 14px;
    }
    h2 {
      font-size: 18px;
      font-weight: bold;
      margin: 18px 0 8px;
    }
    p { margin: 8px 0; }

    /* --- Blur/loading overlay --- */
    .blur-wrap { position: relative; }

    .blurred {
      filter: blur(3.5px);
      opacity: 0.75;
      user-select: none;
      pointer-events: none;
    }

    .overlay {
      position: fixed;
      inset: 0;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,0.45);
      backdrop-filter: blur(1px);
    }

    .loading-box {
      width: min(520px, calc(100% - 40px));
      border: 1px solid #bbb;
      background: #fff;
      padding: 14px 16px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    }

    .loading-row {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Spinner (same idea as your original loader, just sized like the apache version) */
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

    .loading-title {
      font-size: 14px;
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      color: #111;
    }

    .loading-sub {
      margin: 8px 0 0;
      font-size: 12px;
      font-family: Arial, Helvetica, sans-serif;
      color: #444;
    }

    .status {
      margin-top: 10px;
      height: 10px;
      border: 1px solid #bbb;
      background: #f7f7f7;
      overflow: hidden;
    }
    .status > i {
      display: block;
      height: 100%;
      width: 30%;
      background: #cfcfcf;
      animation: move 1.1s ease-in-out infinite;
    }
    @keyframes move {
      0%   { transform: translateX(-120%); }
      100% { transform: translateX(360%); }
    }
  </style>
</head>

<body>
  <div class="blur-wrap">
    <!-- Background: Apache-like page content -->
    <div class="page blurred" aria-hidden="true">
      <h1>Apache HTTP Server Test Page</h1>
      <p>This page is used to test the proper operation of the Apache HTTP server after it has been installed.</p>
      <p>If you can read this page, it means that the Apache HTTP server installed at this site is working properly.</p>

      <hr />

      <h2>Testing 1, 2, 3...</h2>
      <p>This is a simple static HTML page used for testing. If you are the administrator of this website:</p>
      <ul>
        <li>You may now add content to the directory <code>/var/www/html/</code>.</li>
        <li>You should replace this page (index.html) before continuing to operate your HTTP server.</li>
      </ul>

      <h2>Configuration Overview</h2>
      <p>The Apache web server is configured by using the following files:</p>
      <ul>
        <li><code>/etc/httpd/conf/httpd.conf</code></li>
        <li><code>/etc/httpd/conf.d/*.conf</code></li>
      </ul>

      <hr />
      <p><small>Apache is working. (Simulated default page look.)</small></p>
    </div>

    <!-- Foreground: loading overlay -->
    <div class="overlay" role="status" aria-live="polite">
      <div class="loading-box">
        <div class="loading-row">
          <div class="spinner" aria-hidden="true"></div>
          <p class="loading-title"><strong>Apache HTTP Server</strong> — Loading…</p>
        </div>
        <p class="loading-sub">Please wait while the server prepares the requested resource.</p>
        <div class="status" aria-hidden="true"><i></i></div>

        <!-- Keep the same look, just change the meaning -->
        <p class="loading-sub" style="margin-top:10px;">
          Checking again in about 2 seconds…
        </p>
      </div>
    </div>
  </div>
</body>
</html>