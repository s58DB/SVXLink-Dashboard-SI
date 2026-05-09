#!/bin/bash
set -euo pipefail

ENABLED="${1:-0}"
MINUTE="${2:-00}"
HOUR="${3:-20}"
MODE="${4:-once}"
TG="${5:-293}"
DATE_VALUE="${6:-}"
WEEKDAY="${7:-3}"
MONTHDAY="${8:-1}"
CRON_FILE="/etc/cron.d/svxlink-dashboard-tg-schedule"

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

case "$TG" in
  ''|*[!0-9]*) echo "Invalid TG" >&2; exit 1 ;;
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

case "$MODE" in
  once)
    case "$DATE_VALUE" in
      ????-??-??) ;;
      *) echo "Invalid date" >&2; exit 1 ;;
    esac
    MONTH="${DATE_VALUE:5:2}"
    DAY_OF_MONTH="${DATE_VALUE:8:2}"
    DAY_OF_WEEK="*"
    ;;
  weekly)
    case "$WEEKDAY" in
      ''|*[!0-9]*) echo "Invalid weekday" >&2; exit 1 ;;
    esac
    if [ "$WEEKDAY" -lt 1 ] || [ "$WEEKDAY" -gt 7 ]; then
      echo "Weekday out of range" >&2
      exit 1
    fi
    MONTH="*"
    DAY_OF_MONTH="*"
    if [ "$WEEKDAY" = "7" ]; then
      DAY_OF_WEEK="0"
    else
      DAY_OF_WEEK="$WEEKDAY"
    fi
    ;;
  monthly)
    case "$MONTHDAY" in
      ''|*[!0-9]*) echo "Invalid month day" >&2; exit 1 ;;
    esac
    if [ "$MONTHDAY" -lt 1 ] || [ "$MONTHDAY" -gt 31 ]; then
      echo "Month day out of range" >&2
      exit 1
    fi
    MONTH="*"
    DAY_OF_MONTH="$MONTHDAY"
    DAY_OF_WEEK="*"
    ;;
  *)
    echo "Invalid mode" >&2
    exit 1
    ;;
esac

DTMF_CODE="91${TG}#"

cat > "$CRON_FILE" <<EOL
# Managed by SVXLink Dashboard Slovenija.
# Writes the configured TG DTMF command directly for the selected schedule.
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
$MINUTE $HOUR $DAY_OF_MONTH $MONTH $DAY_OF_WEEK svxlink /bin/echo '$DTMF_CODE' > /var/run/svxlink/dtmf_svx
EOL

chmod 644 "$CRON_FILE"
