#!/bin/bash
set -u

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
DEFAULT_AUDIO_DEVICE="${AUDIO_TEST_DEVICE:-plughw:1,1}"
LOG_FILE="$SCRIPT_DIR/record-last.log"

if [ ! -w "$SCRIPT_DIR" ]; then
    LOG_FILE="/tmp/svxlink-audio-record-last.log"
fi

exec > >(tee "$LOG_FILE") 2>&1

echo "SVXLink audio test started: $(date)"
echo "Running as user: $(id -un)"
echo "Audio directory: $SCRIPT_DIR"

if [ ! -w "$SCRIPT_DIR" ]; then
    echo "Audio directory is not writable by user $(id -un): $SCRIPT_DIR" >&2
    echo "Run this test as the web/SVXLink user or fix ownership/permissions." >&2
    exit 1
fi

if ! command -v arecord >/dev/null 2>&1; then
    echo "arecord command not found. Install alsa-utils." >&2
    exit 1
fi

echo "Available capture devices:"
arecord -l || true

record_audio() {
    local target="$1"
    local device="$2"

    device="${device#alsa:}"
    echo "Recording from $device to $target"
    arecord -D "$device" -V mono -r 48000 -f S16_LE -c1 -d 15 "$target"
}

try_recording() {
    local target="$1"
    shift
    local tried=""
    local device

    for device in "$@"; do
        device="${device#alsa:}"
        case " $tried " in
            *" $device "*) continue ;;
        esac
        tried="$tried $device"

        if record_audio "$target" "$device"; then
            return 0
        fi

        rm -f "$target"
    done

    echo "Recording failed. Tried devices:$tried" >&2
    return 1
}

# Record 15 seconds from the SVXLink loopback monitor. Keep the previous good
# recording until a new WAV has been captured successfully.
timestamp="$(date +%Y-%m-%d-%H-%M-%S)"
tmpfile="$SCRIPT_DIR/.audio-$timestamp.tmp.wav"
file="$SCRIPT_DIR/audio-$timestamp.wav"
if ! try_recording "$tmpfile" "$DEFAULT_AUDIO_DEVICE" "plughw:Loop,1,0" "plughw:Loopback,1,0" "plughw:1,1" "plughw:1,0" "rx_monitor" "default"; then
    exit 1
fi

if [ ! -s "$tmpfile" ]; then
    rm -f "$tmpfile"
    echo "Recording did not create a usable WAV file" >&2
    exit 1
fi

mv "$tmpfile" "$file"
chmod 0644 "$file"

find "$SCRIPT_DIR" -maxdepth 1 -name 'audio-*.wav' ! -name "$(basename "$file")" -delete

# Update live.wav symlink
ln -sf "$(basename "$file")" "$SCRIPT_DIR/live.wav" 2>/dev/null || cp "$file" "$SCRIPT_DIR/live.wav"

echo "Recording completed: $file"

sleep 2
