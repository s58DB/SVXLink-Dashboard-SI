#!/bin/bash
set -u

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
DEFAULT_AUDIO_DEVICE="${AUDIO_TEST_DEVICE:-plughw:1,0}"
if [ -w "$SCRIPT_DIR" ]; then
    LOG_FILE="$SCRIPT_DIR/record-last.log"
else
    LOG_FILE="/tmp/svxlink-audio-record-last.log"
fi

exec > >(tee "$LOG_FILE") 2>&1

if [ ! -w "$SCRIPT_DIR" ]; then
    echo "Audio directory is not writable by user $(id -un): $SCRIPT_DIR" >&2
    echo "Run this test as the web/SVXLink user or fix ownership/permissions." >&2
    exit 1
fi

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

# Remove old files
rm -f "$SCRIPT_DIR"/audio-*.wav "$SCRIPT_DIR"/live.wav

# Record 15 seconds from the SVXLink loopback monitor.
file="$SCRIPT_DIR/audio-$(date +%Y-%m-%d-%H-%M-%S).wav"
if ! try_recording "$file" "$DEFAULT_AUDIO_DEVICE" "plughw:1,0" "plughw:Loopback,1,0" "rx_monitor"; then
    exit 1
fi

if [ ! -s "$file" ]; then
    rm -f "$file"
    echo "Recording did not create a usable WAV file" >&2
    exit 1
fi

# Update live.wav symlink
ln -sf "$file" "$SCRIPT_DIR/live.wav"

sleep 2
