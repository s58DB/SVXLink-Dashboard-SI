#!/bin/bash
# Recording test audio for level measurement
set -u

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
AUDIO_DEVICE="${AUDIO_TEST_DEVICE:-plughw:Loopback,1,0}"

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
if ! arecord -D "$AUDIO_DEVICE" -V mono -r 48000 -f S16_LE -c1 -d 15 "$file"; then
    if [ "$AUDIO_DEVICE" != "rx_monitor" ]; then
        arecord -D rx_monitor -V mono -r 48000 -f S16_LE -c1 -d 15 "$file"
    else
        exit 1
    fi
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
