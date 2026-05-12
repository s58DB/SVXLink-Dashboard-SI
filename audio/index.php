<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$audioDir = __DIR__;
$message = "";
$messageClass = "";
$recordLog = $audioDir . '/record-last.log';
$fallbackRecordLog = '/tmp/svxlink-audio-record-last.log';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (isset($_POST['recAudio'])) {
    $recordScript = $audioDir . '/record.sh';
    $output = array();
    $returnCode = 1;

    if (!is_file($recordScript)) {
        $message = "Recording script not found: " . h($recordScript);
        $messageClass = "red";
    } else {
        exec('bash ' . escapeshellarg($recordScript) . ' 2>&1', $output, $returnCode);
        if ($returnCode === 0) {
            $message = "Recording completed. Play the latest audio file below.";
            $messageClass = "green";
        } else {
            $message = "Recording failed: " . h(implode(" | ", array_slice($output, -4)));
            $messageClass = "red";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link href="/css/css.php" type="text/css" rel="stylesheet" />
<style type="text/css">
body {
  background-color: #eee;
  font-size: 18px;
  font-family: Arial;
  font-weight: 300;
  margin: 2em auto;
  max-width: 40em;
  line-height: 1.5;
  color: #444;
  padding: 0 0.5em;
}
h1, h2, h3 { line-height: 1.2; }
a { color: #607d8b; }
#player audio { width:100%; border-radius:8px; }
</style>
<script src="web-audio-peak-meter.js"></script>
</head>
<body style="background-color:#e1e1e1;font:11pt arial, sans-serif;">
<center>

<fieldset style="border:#3083b8 2px groove; box-shadow:5px 5px 20px #999; background-color:#f1f1f1; width:500px; margin-top:15px; font-size:13px; border-radius:10px; padding:0;">
  <div style="width:100%; padding:10px; background-image: linear-gradient(to bottom, #e9e9e9 50%, #bcbaba 100%); border-radius:10px; border:1px solid LightGrey; box-sizing:border-box;">
    <center>
      <h1 style="color:#00aee8; font:18pt arial, sans-serif; font-weight:bold; text-shadow:0.25px 0.25px gray;">SVXLink Audio Test Peak Meter</h1>
      <p style="font-size:14px; color:#454545; font-weight:bold;">
        Ideal Audio Level: <span style="color:brown;"><b>-15</b> to <b>-10dB</b></span>,
        Max top Audio level (peak) <span style="color:brown;"><b>-10dB</b></span>.
      </p>

      <!-- Peak meter fieldset -->
      <fieldset style="border:rgb(255, 156, 42) 2px groove; box-shadow:5px 5px 20px rgb(255,236,214); background-color:#f1f1f1; width:100%; margin-top:15px; font-size:13px; border-radius:10px; padding:10px; box-sizing:border-box;">
        <div id="my-peak-meter" style="width:100%; height:65px;"></div>
      </fieldset>
    </center>
  </div>
</fieldset>

<p style="margin-top:30px;"></p>
<?php
$filelist = glob($audioDir . '/audio-*.wav');
usort($filelist, function($a, $b) {
    return filemtime($b) <=> filemtime($a);
});

if (!empty($filelist)) {
    $latestFile = $filelist[0];
    $latestUrl = basename($latestFile);
    echo '<div id="player">';
    echo '<p style="font-size:12px;color:#454545;">Latest recording: <b>' . h(basename($latestFile)) . '</b> (' . h(filesize($latestFile)) . ' bytes)</p>';
    echo '<audio id="my-audio" preload="auto" controls style="width:100%; display:block; border-radius:8px; box-sizing:border-box;">';
    echo '<source src="' . h($latestUrl) . '?t=' . time() . '" type="audio/wav">';
    echo '</audio></div>';
    echo '<div id="audio-status" style="font-size:12px;color:#454545;margin-top:6px;"></div>';
} else {
    echo '<p style="font-size:12px;color:#8a4d00;">No audio recording found yet. Click the record button and transmit audio during the 15 second window.</p>';
}
?>



<script>
window.addEventListener('DOMContentLoaded', function() {
    var myAudio = document.getElementById('my-audio');
    var meterElement = document.getElementById('my-peak-meter');
    var statusElement = document.getElementById('audio-status');
    var audioCtx = null;
    var meterReady = false;

    function setStatus(text, color) {
        if (statusElement) {
            statusElement.textContent = text;
            statusElement.style.color = color || '#454545';
        }
    }

    function initMeter() {
        if (!myAudio || !meterElement || meterReady) {
            return;
        }

        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            var sourceNode = audioCtx.createMediaElementSource(myAudio);
            var meterNode = webAudioPeakMeter.createMeterNode(sourceNode, audioCtx);
            webAudioPeakMeter.createMeter(meterElement, meterNode, {});
            meterReady = true;
        } catch (err) {
            setStatus('Peak meter could not start: ' + err.message, '#a00000');
        }
    }

    if (myAudio && meterElement) {
        myAudio.addEventListener('canplay', function() {
            setStatus('Recording loaded. Press play to view the peak meter.');
        });
        myAudio.addEventListener('error', function() {
            setStatus('Browser could not load the WAV recording.', '#a00000');
        });
        myAudio.addEventListener('play', function() {
            initMeter();
            if (audioCtx) {
                audioCtx.resume();
            }
        });
    }
});

// Button feedback during recording
function func() {
    var btn = document.getElementById('runRec');
    btn.value = 'Recording audio, please wait... ';
    btn.className = 'orange';
}
</script>

<form method="post">
    <input name="recAudio" type="submit" class="red" onclick="func()" id="runRec" value="Click to record 15sec" style="height:45px;font-size:18px;">
</form>

<?php
if ($message !== "") {
    $style = ($messageClass === "green") ? "color:green;" : "color:#a00000;";
    echo '<p style="font-size:12px;font-weight:bold;' . $style . '">' . $message . '</p>';
}

$visibleLog = is_file($recordLog) ? $recordLog : (is_file($fallbackRecordLog) ? $fallbackRecordLog : "");
if ($visibleLog !== "") {
    $logLines = file($visibleLog, FILE_IGNORE_NEW_LINES);
    $logTail = implode("\n", array_slice($logLines ?: array(), -40));
    if ($logTail !== "") {
        echo '<details style="margin-top:12px;text-align:left;font-size:12px;max-width:500px;">';
        echo '<summary style="cursor:pointer;font-weight:bold;color:#003366;">Last recording log</summary>';
        echo '<pre style="white-space:pre-wrap;background:#111d33;color:#ffffff;padding:8px;border-radius:4px;">' . h($logTail) . '</pre>';
        echo '</details>';
    }
}
?>

</center>
</body>
</html>
