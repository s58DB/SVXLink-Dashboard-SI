<?php
$schedule = array(
    "enabled" => false,
    "mode" => "first_wednesday",
    "tg" => "293",
    "hour" => "20",
    "minute" => "00",
    "date" => date("Y-m-d"),
    "weekday" => "3",
);

$configFile = __DIR__ . "/../include/config.schedule.php";
if (file_exists($configFile)) {
    include $configFile;
}

$schedule = array_merge(array(
    "enabled" => false,
    "mode" => "first_wednesday",
    "tg" => "293",
    "hour" => "20",
    "minute" => "00",
    "date" => date("Y-m-d"),
    "weekday" => "3",
), $schedule);

if (!$schedule["enabled"]) {
    exit(0);
}

$mode = in_array($schedule["mode"], array("first_wednesday", "once", "weekly"), true)
    ? $schedule["mode"]
    : "first_wednesday";

$shouldRun = false;
switch ($mode) {
    case "once":
        $shouldRun = date("Y-m-d") === $schedule["date"];
        break;
    case "weekly":
        $shouldRun = date("N") === (string) (int) $schedule["weekday"];
        break;
    case "first_wednesday":
    default:
        $shouldRun = date("N") === "3" && (int) date("j") <= 7;
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
