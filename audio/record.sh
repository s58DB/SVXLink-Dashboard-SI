#!/bin/bash
set -u

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
AUDIO_DEVICE="${AUDIO_TEST_DEVICE:-plughw:Loopback,1,0}"
LOG_FILE="$SCRIPT_DIR/record-last.log"

exec > >(tee "$LOG_FILE") 2>&1

record_audio() {
    local target="$1"
    local device="$2"

    echo "Recording from $device to $target"
    arecord -D "$device" -V mono -r 48000 -f S16_LE -c1 -d 15 "$target"
}

# Remove old files
rm -f "$SCRIPT_DIR"/audio-*.wav "$SCRIPT_DIR"/live.wav

# Record 15 seconds from the SVXLink loopback monitor.
file="$SCRIPT_DIR/audio-$(date +%Y-%m-%d-%H-%M-%S).wav"
if ! record_audio "$file" "$AUDIO_DEVICE"; then
    rm -f "$file"
    if [ "$AUDIO_DEVICE" != "rx_monitor" ]; then
        echo "Primary audio device failed, trying rx_monitor fallback"
        if ! record_audio "$file" "rx_monitor"; then
            rm -f "$file"
            echo "Recording failed on $AUDIO_DEVICE and rx_monitor" >&2
            exit 1
        fi
    else
        exit 1
    fi
fi

if [ ! -s "$file" ]; then
    rm -f "$file"
    echo "Recording did not create a usable WAV file" >&2
    exit 1
fi

# Update live.wav symlink
ln -sf "$file" "$SCRIPT_DIR/live.wav"

sleep 2
