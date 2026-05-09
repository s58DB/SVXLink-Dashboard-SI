<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . "/auth.php";
include_once __DIR__ . "/config.php";
include_once __DIR__ . "/functions.php";

if (!isAuthorised()) {
    renderUnauthorisedMessage("Za ogled SVXReflector debug podatkov se morate prijaviti.");
    return;
}

function reflector_debug_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function reflector_debug_rows($rows, $limit = 30) {
    $shown = 0;
    foreach ($rows as $row) {
        if ($shown >= $limit) {
            break;
        }

        if (trim((string)$row) === '') {
            continue;
        }

        echo '<tr><td><code>' . reflector_debug_h($row) . '</code></td></tr>' . "\n";
        $shown++;
    }
}

function reflector_debug_recent_log_lines($limit = 80) {
    $logPath = SVXLOGPATH . SVXLOGPREFIX;

    if (!file_exists($logPath)) {
        return array("Log file not found: " . $logPath);
    }

    $output = shell_exec('tail -' . intval($limit) . ' ' . escapeshellarg($logPath) . ' 2>&1');
    if ($output === null || $output === false || $output === '') {
        return array("No output from tail for: " . $logPath);
    }

    return explode("\n", trim($output));
}

$rawLogLines = getSVXLog();
$rawLogLines = array_values(array_filter($rawLogLines, function ($line) {
    return trim((string)$line) !== '';
}));

$sortedLogLines = $rawLogLines;
array_multisort($sortedLogLines, SORT_DESC);

$heardList = getHeardList($sortedLogLines);
$lastHeardDebug = getLastHeard($sortedLogLines);
$recentLogLines = reflector_debug_recent_log_lines(80);
$latestEvents = array();
foreach ($sortedLogLines as $line) {
    if (!preg_match('/Talker (start|stop) on TG #([^:]+):\s*(.+)$/', $line, $matches)) {
        continue;
    }

    $callsign = trim($matches[3]);
    $tg = 'TG ' . trim($matches[2]);
    $key = $callsign . '#' . $tg;

    if (!isset($latestEvents[$key])) {
        $latestEvents[$key] = array(
            'callsign' => $callsign,
            'tg' => $tg,
            'event' => strtoupper($matches[1]),
            'line' => $line,
        );
    }
}
?>
<span style="font-weight:bold;font-size:14px;">SVXReflector Activity Debug</span>
<fieldset style="width:850px;box-shadow:5px 5px 20px #999;background-color:#e8e8e8e8;margin-top:10px;font-size:12px;border-radius:10px;">
  <p style="text-align:left;margin:8px 10px;">
    Ta stran samo bere log in prikaze, kako obstojece funkcije sestavijo SVXReflector Activity tabelo.
    Samodejno se osvezi vsako sekundo. Refresh: <?php echo reflector_debug_h(date('H:i:s')); ?>
  </p>

  <h3 style="text-align:left;margin-left:10px;">Končni getLastHeard()</h3>
  <table style="width:830px;margin:0 10px 14px 10px;">
    <tr>
      <th>Time</th>
      <th>Callsign</th>
      <th>TG</th>
      <th>TX</th>
      <th>Source</th>
      <th>tx.gif?</th>
      <th>strtotime</th>
      <th>diff now</th>
    </tr>
<?php foreach (array_slice($lastHeardDebug, 0, 20) as $row) { ?>
<?php $parsedTime = strtotime($row[0]); ?>
    <tr>
      <td><?php echo reflector_debug_h($row[0]); ?></td>
      <td><b><?php echo reflector_debug_h($row[1]); ?></b></td>
      <td><?php echo reflector_debug_h($row[2]); ?></td>
      <td><?php echo reflector_debug_h($row[3]); ?></td>
      <td><?php echo reflector_debug_h($row[4]); ?></td>
      <td><?php echo $row[3] === "ON" ? "DA" : "NE"; ?></td>
      <td><?php echo $parsedTime === false ? "false" : reflector_debug_h(date('Y-m-d H:i:s', $parsedTime)); ?></td>
      <td><?php echo $parsedTime === false ? "-" : reflector_debug_h(time() - $parsedTime); ?></td>
    </tr>
<?php } ?>
  </table>

  <h3 style="text-align:left;margin-left:10px;">Zadnji raw dogodek po callsign+TG</h3>
  <table style="width:830px;margin:0 10px 14px 10px;">
    <tr>
      <th>Callsign</th>
      <th>TG</th>
      <th>Event</th>
      <th>Raw line</th>
    </tr>
<?php foreach (array_slice($latestEvents, 0, 30) as $event) { ?>
    <tr>
      <td><b><?php echo reflector_debug_h($event['callsign']); ?></b></td>
      <td><?php echo reflector_debug_h($event['tg']); ?></td>
      <td><?php echo reflector_debug_h($event['event']); ?></td>
      <td><code><?php echo reflector_debug_h($event['line']); ?></code></td>
    </tr>
<?php } ?>
  </table>

  <h3 style="text-align:left;margin-left:10px;">Vsi parsed getHeardList() zapisi</h3>
  <table style="width:830px;margin:0 10px 14px 10px;">
    <tr>
      <th>Time</th>
      <th>Callsign</th>
      <th>TG</th>
      <th>TX</th>
      <th>Key</th>
      <th>strtotime</th>
      <th>diff now</th>
    </tr>
<?php foreach (array_slice($heardList, 0, 50) as $row) { ?>
<?php $parsedTime = strtotime($row[0]); ?>
    <tr>
      <td><?php echo reflector_debug_h($row[0]); ?></td>
      <td><b><?php echo reflector_debug_h($row[1]); ?></b></td>
      <td><?php echo reflector_debug_h($row[2]); ?></td>
      <td><?php echo reflector_debug_h($row[3]); ?></td>
      <td><?php echo reflector_debug_h($row[1] . "#" . $row[2]); ?></td>
      <td><?php echo $parsedTime === false ? "false" : reflector_debug_h(date('Y-m-d H:i:s', $parsedTime)); ?></td>
      <td><?php echo $parsedTime === false ? "-" : reflector_debug_h(time() - $parsedTime); ?></td>
    </tr>
<?php } ?>
  </table>

  <h3 style="text-align:left;margin-left:10px;">Zadnjih 80 raw log vrstic</h3>
  <table style="width:830px;margin:0 10px 14px 10px;">
<?php reflector_debug_rows(array_reverse($recentLogLines), 80); ?>
  </table>

  <h3 style="text-align:left;margin-left:10px;">Sortirane raw Talker vrstice</h3>
  <table style="width:830px;margin:0 10px 14px 10px;">
<?php reflector_debug_rows($sortedLogLines, 40); ?>
  </table>

  <h3 style="text-align:left;margin-left:10px;">Raw Talker vrstice iz tail</h3>
  <table style="width:830px;margin:0 10px 14px 10px;">
<?php reflector_debug_rows(array_reverse($rawLogLines), 40); ?>
  </table>
</fieldset>
