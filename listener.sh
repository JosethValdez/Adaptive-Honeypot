#!/bin/bash

PORT=54321

while true; do
  echo "Listening on port $PORT..."
  ncat -l 127.0.0.1 $PORT --sh-exec "bash handler.sh"
done