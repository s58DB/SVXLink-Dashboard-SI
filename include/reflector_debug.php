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

$rawLogLines = getSVXLog();
$rawLogLines = array_values(array_filter($rawLogLines, function ($line) {
    return trim((string)$line) !== '';
}));

$sortedLogLines = $rawLogLines;
array_multisort($sortedLogLines, SORT_DESC);

$heardList = getHeardList($sortedLogLines);
$lastHeardDebug = getLastHeard($sortedLogLines);
?>
<span style="font-weight:bold;font-size:14px;">SVXReflector Activity Debug</span>
<fieldset style="width:850px;box-shadow:5px 5px 20px #999;background-color:#e8e8e8e8;margin-top:10px;font-size:12px;border-radius:10px;">
  <p style="text-align:left;margin:8px 10px;">
    Ta stran samo bere log in prikaze, kako obstojece funkcije sestavijo SVXReflector Activity tabelo.
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
    </tr>
<?php foreach (array_slice($lastHeardDebug, 0, 20) as $row) { ?>
    <tr>
      <td><?php echo reflector_debug_h($row[0]); ?></td>
      <td><b><?php echo reflector_debug_h($row[1]); ?></b></td>
      <td><?php echo reflector_debug_h($row[2]); ?></td>
      <td><?php echo reflector_debug_h($row[3]); ?></td>
      <td><?php echo reflector_debug_h($row[4]); ?></td>
      <td><?php echo $row[3] === "ON" ? "DA" : "NE"; ?></td>
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
    </tr>
<?php foreach (array_slice($heardList, 0, 50) as $row) { ?>
    <tr>
      <td><?php echo reflector_debug_h($row[0]); ?></td>
      <td><b><?php echo reflector_debug_h($row[1]); ?></b></td>
      <td><?php echo reflector_debug_h($row[2]); ?></td>
      <td><?php echo reflector_debug_h($row[3]); ?></td>
      <td><?php echo reflector_debug_h($row[1] . "#" . $row[2]); ?></td>
    </tr>
<?php } ?>
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
