#!/bin/bash
# Manual wrapper for the dashboard audio recording test.
set -u

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"

"$SCRIPT_DIR/record.sh"

echo ""
MYIP=$(hostname -I 2>/dev/null | awk '{print $1}')
if [ -n "$MYIP" ]; then
    echo "You can now listen to your audio on the svxlink dashboard page:"
    echo "http://$MYIP/audio/"
else
    echo "You can now listen to your audio on the svxlink dashboard audio page."
fi
