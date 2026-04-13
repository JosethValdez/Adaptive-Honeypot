# AI-Generated PHP Micro-Site (Ollama + PHP Router)

This project is a small local “micro-site” that **generates PHP pages on demand** using an Ollama model.  
Instead of pre-writing every page, you click an internal link and the server will:

1. trigger a Python generator (Ollama),
2. show a lightweight loading screen that refreshes every ~2 seconds,
3. redirect you to the newly generated `.php` page once it’s ready.

It’s designed to look like an **old-school late 90s / early 2000s internal server site**.

---

## How it works (high level)

### 1) Homepage generation (`index.php`)
- `web_agent.py` generates `index.php` using `ollama.chat(...)`.
- While generation is happening, `loading.php` can be used to poll for `index.ready` and redirect to `index.php` when ready.

### 2) On-demand page generation (any `*.php` page)
- Every internal link must go through:

  `go.php?p=SomePage.php&label=Some Topic`

- `go.php` sanitizes the file name, logs the click, and checks:
  - If `SomePage.php` **and** `SomePage.ready` already exist: redirect straight to `SomePage.php`
  - Otherwise: start `page_agent.py` in the background and redirect to `gen_loading.php?p=SomePage.php`

- `gen_loading.php` refreshes every ~2 seconds until `SomePage.ready` exists, then redirects to `SomePage.php`.

### 3) “Ready flag” files (`*.ready`)
Each generated page has a matching `*.ready` marker:
- Python deletes the marker before generating
- Python writes the page file
- Python creates the marker when generation is complete
- PHP loading pages poll for the marker and redirect when ready

---

## Files

### PHP
- **`go.php`**
  - Main router for internal links.
  - Requires `?p=...` (no silent default).
  - Starts generation when needed and redirects to the loader.
  - Logs incoming clicks to `clicks.log`.

- **`gen_loading.php`**
  - Generic loader for generated pages.
  - Polls for `<page>.ready` then redirects to `<page>.php`.

- **`loading.php`**
  - Loader page specifically for the homepage (`index.php`).
  - Polls for `index.ready` then redirects to `index.php`.

### Python (Ollama generators)
- **`web_agent.py`**
  - Generates `index.php` (“Server Links”) using Ollama.
  - Writes `index.ready` when done.

- **`page_agent.py`**
  - Generates a requested page file, e.g. `status.php`, `docs.php`, etc.
  - Usage pattern:
    - `page_agent.py <filename.php> [label]`
  - Writes `<filename>.ready` when done.

### Shell (optional / experimental)
- **`listener.sh`** and **`handler.sh`**
  - Alternative method that listens on a local port (via `ncat`) and returns an HTTP redirect to `loading.php` while running `web_agent.py`.
  - These are optional and mainly useful if you’re experimenting outside Apache/PHP routing.

---

## Requirements

- **PHP** (commonly via XAMPP/WAMP/MAMP or Apache + PHP)
- **Python 3**
- **Ollama** installed and running locally
- Python package:
  - `ollama` (`pip install ollama`)
- The scripts currently reference this model:
  - `qwen2.5-coder:1.5b`
  - Make sure it’s available locally in Ollama.

> Note: `go.php` uses a Windows-style background launch command (`cmd /c start ...`) and is intended for Windows/XAMPP.

---

## Setup (Windows / XAMPP style)

1. Copy all project files into your Apache web root (example):
   - `C:\xampp\htdocs\server\`

2. Start Apache in XAMPP.

3. Ensure Ollama is running and the model is available, for example:
   - `ollama pull qwen2.5-coder:1.5b`

4. Generate the homepage:
   - Run:
     - `python web_agent.py`
   - Then open:
     - `http://localhost/server/index.php`
   - Or open the loader (waits until `index.ready` exists):
     - `http://localhost/server/loading.php`

5. Click links on the homepage:
   - They go through `go.php`, which generates missing pages and sends you to `gen_loading.php` until the page is ready.

---

## Notes / Behavior

- **All links are internal** and must route through `go.php`.  
  The generators are prompted to output links in this exact format:

  ```html
  <a href="go.php?p=FILENAME.php&label=<?php echo urlencode('LINK TEXT'); ?>">LINK TEXT</a>
  ```

- `go.php` writes a simple request log to:
  - `clicks.log`

- Generated pages and `*.ready` files are written into the same directory as the scripts.

---

## Security / Disclaimer

This is a local demo/prototype. If you expose it to the public internet, you should harden it further:
- add auth,
- rate limit / queue generation,
- isolate generation,
- restrict filesystem permissions,
- validate inputs even more strictly.

---

## License

Add your preferred license (MIT/Apache-2.0/etc.) if you plan to share publicly
