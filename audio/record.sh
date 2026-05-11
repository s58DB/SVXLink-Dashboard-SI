#!/bin/bash
set -u

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
AUDIO_DEVICE="${AUDIO_TEST_DEVICE:-plughw:Loopback,1,0}"

# Remove old files
rm -f "$SCRIPT_DIR"/audio-*.wav "$SCRIPT_DIR"/live.wav

# Record 15 seconds from the SVXLink loopback monitor.
file="$SCRIPT_DIR/audio-$(date +%Y-%m-%d-%H-%M-%S).wav"
if ! arecord -D "$AUDIO_DEVICE" -V mono -r 48000 -f S16_LE -c1 -d 15 "$file"; then
    if [ "$AUDIO_DEVICE" != "rx_monitor" ]; then
        arecord -D rx_monitor -V mono -r 48000 -f S16_LE -c1 -d 15 "$file"
    else
        exit 1
    fi
fi

# Update live.wav symlink
ln -sf "$file" "$SCRIPT_DIR/live.wav"

sleep 2
