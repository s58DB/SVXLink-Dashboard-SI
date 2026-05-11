#!/bin/bash
# Recording test audio for level measurement
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

reset
# remove old files
count=`ls -1 "$SCRIPT_DIR"/audio-*.wav 2>/dev/null | wc -l`
if [ $count != 0 ]
then
rm -f "$SCRIPT_DIR"/audio-*.wav "$SCRIPT_DIR"/live.wav
fi
echo ""
echo "Audio recording 10 seconds, to stop recording before 10 seconds use CTRL+C"
echo " "
#arecord -D hw:Loopback,1,1 -V mono -r 48000 -f S16_LE -c1 -d 15 /var/www/html/audio/audio-$(date +%Y-%m-%d-%H -%M-%S).wav
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

ln -sf "$file" "$SCRIPT_DIR/live.wav"

echo ""
MYIP=$(hostname -I | awk '{print $1}')
echo " "
echo "You can now listen to your audio on the svxlink dashboard page:"
echo ""
echo "http://$MYIP/audio"
echo ""
echo ""
