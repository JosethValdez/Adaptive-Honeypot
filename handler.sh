#!/bin/bash

PYTHON_SCRIPT="web_agent.py"
TARGET="http://localhost/server/loading.php"

python "$PYTHON_SCRIPT" &

printf "HTTP/1.1 302 Found\r\n"
printf "Location: %s\r\n" "$TARGET"
printf "Connection: close\r\n\r\n"