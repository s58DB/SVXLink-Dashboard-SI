<?php
$schedule = array(
    "enabled" => false,
    "mode" => "once",
    "tg" => "293",
    "hour" => "20",
    "minute" => "00",
    "date" => date("Y-m-d"),
    "weekday" => "3",
    "monthday" => "1",
);

$configFile = __DIR__ . "/../include/config.schedule.php";
if (file_exists($configFile)) {
    include $configFile;
}

$schedule = array_merge(array(
    "enabled" => false,
    "mode" => "once",
    "tg" => "293",
    "hour" => "20",
    "minute" => "00",
    "date" => date("Y-m-d"),
    "weekday" => "3",
    "monthday" => "1",
), $schedule);

if (!$schedule["enabled"]) {
    exit(0);
}

$mode = $schedule["mode"] === "first_wednesday" ? "monthly" : $schedule["mode"];
$mode = in_array($mode, array("once", "weekly", "monthly"), true)
    ? $mode
    : "once";

$shouldRun = false;
switch ($mode) {
    case "once":
        $shouldRun = date("Y-m-d") === $schedule["date"];
        break;
    case "weekly":
        $shouldRun = date("N") === (string) (int) $schedule["weekday"];
        break;
    case "monthly":
        $shouldRun = (int) date("j") === min(31, max(1, (int) $schedule["monthday"]));
        break;
    default:
        $shouldRun = false;
        break;
}

if (!$shouldRun) {
    exit(0);
}

$tg = preg_replace("/[^0-9]/", "", $schedule["tg"]);
if ($tg === "") {
    exit(1);
}

file_put_contents("/var/run/svxlink/dtmf_svx", "91" . $tg . "#");
?>
