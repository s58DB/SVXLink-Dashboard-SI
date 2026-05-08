#!/bin/bash
set -euo pipefail

ENABLED="${1:-0}"
MINUTE="${2:-00}"
HOUR="${3:-20}"
CRON_FILE="/etc/cron.d/svxlink-dashboard-tg-schedule"
RUNNER="/var/www/html/scripts/tg_schedule_runner.php"

case "$ENABLED" in
  0|1) ;;
  *) echo "Invalid enabled value" >&2; exit 1 ;;
esac

case "$MINUTE" in
  ''|*[!0-9]*) echo "Invalid minute" >&2; exit 1 ;;
esac

case "$HOUR" in
  ''|*[!0-9]*) echo "Invalid hour" >&2; exit 1 ;;
esac

if [ "$MINUTE" -lt 0 ] || [ "$MINUTE" -gt 59 ]; then
  echo "Minute out of range" >&2
  exit 1
fi

if [ "$HOUR" -lt 0 ] || [ "$HOUR" -gt 23 ]; then
  echo "Hour out of range" >&2
  exit 1
fi

if [ "$ENABLED" = "0" ]; then
  rm -f "$CRON_FILE"
  exit 0
fi

cat > "$CRON_FILE" <<EOL
# Managed by SVXLink Dashboard Slovenija.
# Runs at the configured time; the PHP runner checks for first Wednesday.
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
$MINUTE $HOUR * * * svxlink /usr/bin/php $RUNNER
EOL

chmod 644 "$CRON_FILE"
