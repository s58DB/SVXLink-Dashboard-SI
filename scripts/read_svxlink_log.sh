#!/bin/bash
set -euo pipefail

for LOG_FILE in /var/log/svxlink.log /var/log/svxlink /var/log/svxlink.log.1; do
  if [ -r "$LOG_FILE" ]; then
    tail -n 80 "$LOG_FILE"
    exit 0
  fi
done

echo "SVXLink log file not found or not readable: /var/log/svxlink.log"
exit 1
