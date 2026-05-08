
<?php
include_once __DIR__ . "/tools.php";
include_once __DIR__ . "/config.buttons.php";
include_once __DIR__ . "/auth.php";

$scheduleConfigFile = __DIR__ . "/config.schedule.php";
$schedule = array(
    "enabled" => false,
    "tg" => "293",
    "hour" => "20",
    "minute" => "00",
);

if (file_exists($scheduleConfigFile)) {
    include $scheduleConfigFile;
}

function installScheduleCron($schedule) {
    $script = realpath(__DIR__ . "/../scripts/install_tg_schedule_cron.sh");
    if ($script === false) {
        return array(1, array("Cron installer script not found."));
    }

    $enabled = $schedule["enabled"] ? "1" : "0";
    $command = "sudo " . escapeshellarg($script) . " " .
        escapeshellarg($enabled) . " " .
        escapeshellarg($schedule["minute"]) . " " .
        escapeshellarg($schedule["hour"]) . " 2>&1";

    $output = array();
    $returnCode = 0;
    exec($command, $output, $returnCode);
    return array($returnCode, $output);
}

$scheduleMessage = "";
$authorised = isAuthorised();

if (isset($_POST["save_schedule"]) && !$authorised) {
    $scheduleMessage = "Schedule ni shranjen. Za spremembe se morate najprej prijaviti kot sysop.";
}

if (isset($_POST["save_schedule"]) && $authorised) {
    $schedule = array(
        "enabled" => isset($_POST["schedule_enabled"]),
        "tg" => preg_replace("/[^0-9]/", "", $_POST["schedule_tg"]),
        "hour" => str_pad((string) min(23, max(0, (int) $_POST["schedule_hour"])), 2, "0", STR_PAD_LEFT),
        "minute" => str_pad((string) min(59, max(0, (int) $_POST["schedule_minute"])), 2, "0", STR_PAD_LEFT),
    );

    if ($schedule["tg"] === "") {
        $schedule["tg"] = "293";
    }

    $config = "<?php\n";
    $config .= "\$schedule = " . var_export($schedule, true) . ";\n";
    $config .= "?>\n";
    file_put_contents($scheduleConfigFile, $config);
    list($cronStatus, $cronOutput) = installScheduleCron($schedule);
    if ($cronStatus === 0) {
        $scheduleMessage = "Schedule je shranjen in cron zapis je posodobljen.";
    } else {
        $scheduleMessage = "Schedule je shranjen, cron zapis pa ni uspel. Preverite sudoers nastavitev iz upgrade.sh.";
    }
}

$cronLine = $schedule["minute"] . " " . $schedule["hour"] . " * * * svxlink /usr/bin/php /var/www/html/scripts/tg_schedule_runner.php";
?>

<div class="content">
<fieldset class="control-panel">
<div class="control-panel-inner">
<p class="control-panel-title">DTMF / TG operativni gumbi</p>

<?php
if (!$authorised) {
    renderUnauthorisedMessage("Niste avtorizirani. Za DTMF ukaze, schedule in nastavitve se prijavite kot sysop.");
} else {
    if ($scheduleMessage !== "") {
        echo '<div class="schedule-status">' . htmlspecialchars($scheduleMessage, ENT_QUOTES) . '</div>';
    }
?>

<form method="post">
    <div class="button-row">
    <?php
    // Generate buttons dynamically
    for ($i = 1; $i <= 20; $i++) {
        if (defined("KEY$i") && constant("KEY$i")[0] != "") {
            $label = constant("KEY$i")[0];
            $color = constant("KEY$i")[2];
            echo "<input type='submit' name='button$i' class='$color' value='$label' /> ";
        }
    }
    ?>
    </div>
</form>

<form action="" method="POST" style="margin-top:4px;">
    <center>
        <label style="text-shadow:1px 1px 1px Lightgrey,0 0 0.5em LightGrey,0 0 1em whitesmoke;font-weight:bold;color:#464646;" for="dtmfsvx">DTMF command (must end with #):</label>  
        <input type="text" id="dtmfsvx" name="dtmfsvx">
        <input type="submit" value="Send DTMF code" class="green"><br>
    </center>
</form>

<form action="" method="POST" class="schedule-panel">
    <p class="control-panel-title">Schedule povezave: vsako prvo sredo v mesecu</p>
    <div class="schedule-grid">
        <div>
            <label for="schedule_tg">TG</label>
            <input type="text" id="schedule_tg" name="schedule_tg" value="<?php echo htmlspecialchars($schedule["tg"], ENT_QUOTES); ?>" />
        </div>
        <div>
            <label for="schedule_hour">Ura</label>
            <input type="number" id="schedule_hour" name="schedule_hour" min="0" max="23" value="<?php echo htmlspecialchars($schedule["hour"], ENT_QUOTES); ?>" />
        </div>
        <div>
            <label for="schedule_minute">Min</label>
            <input type="number" id="schedule_minute" name="schedule_minute" min="0" max="59" value="<?php echo htmlspecialchars($schedule["minute"], ENT_QUOTES); ?>" />
        </div>
        <div>
            <label for="schedule_enabled">Aktivno</label>
            <input type="checkbox" id="schedule_enabled" name="schedule_enabled" <?php echo $schedule["enabled"] ? "checked" : ""; ?> />
        </div>
        <div>
            <input type="submit" name="save_schedule" value="Shrani schedule" class="blue" />
        </div>
    </div>
    <p class="schedule-status">
        DTMF ob izvedbi: <?php echo "91" . htmlspecialchars($schedule["tg"], ENT_QUOTES) . "#"; ?>.
        Cron vrstica za streznik:
        <code class="schedule-cron"><?php echo htmlspecialchars($cronLine, ENT_QUOTES); ?></code>
    </p>
</form>

<?php
}

// Handle DTMF button presses
if ($authorised) {
    for ($i = 1; $i <= 20; $i++) {
        $buttonName = "button$i";
        if (array_key_exists($buttonName, $_POST)) {
            $dtmfCode = constant("KEY$i")[1]; // DTMF code
            $exec = "echo '$dtmfCode' > /var/run/svxlink/dtmf_svx";
            exec($exec);
            echo "<meta http-equiv='refresh' content='0'>";
        }
    }

    // Handle DTMF input field
    if (isset($_POST["dtmfsvx"])) {
        $exec = "echo '" . $_POST['dtmfsvx'] . "' > /var/run/svxlink/dtmf_svx";
        exec($exec);
        echo "<meta http-equiv='refresh' content='0'>";
    }

    // Handle jmpto commands
    foreach (['jmpto', 'jmptoA', 'jmptoM'] as $field) {
        if (isset($_POST[$field])) {
            $prefix = ($field === 'jmptoM') ? '94' : '91';
            $exec = "echo '$prefix" . $_POST[$field] . "#' > /var/run/svxlink/dtmf_svx";
            exec($exec);
            echo "<meta http-equiv='refresh' content='0'>";
        }
    }
}
?>
</div>
</fieldset>
</div>
