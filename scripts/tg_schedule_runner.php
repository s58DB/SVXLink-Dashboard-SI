<?php
$schedule = array(
    "enabled" => false,
    "tg" => "293",
    "hour" => "20",
    "minute" => "00",
);

$configFile = __DIR__ . "/../include/config.schedule.php";
if (file_exists($configFile)) {
    include $configFile;
}

if (!$schedule["enabled"]) {
    exit(0);
}

$isFirstWednesday = date("N") === "3" && (int) date("j") <= 7;
if (!$isFirstWednesday) {
    exit(0);
}

$tg = preg_replace("/[^0-9]/", "", $schedule["tg"]);
if ($tg === "") {
    exit(1);
}

file_put_contents("/var/run/svxlink/dtmf_svx", "91" . $tg . "#");
?>
