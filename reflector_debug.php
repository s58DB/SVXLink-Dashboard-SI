<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "include/settings.php";
include "include/config.php";
include_once "include/auth.php";
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="pragma" content="no-cache" />
    <link rel="shortcut icon" href="images/favicon.ico" sizes="16x16 32x32" type="image/png">
    <link href="css/css.php" type="text/css" rel="stylesheet" />
    <?php echo ("<title>" . $callsign ." SVXReflector Debug</title>" ); ?>
</head>
<body style="font: 11pt arial, sans-serif;">
<center>
<fieldset style="box-shadow:0 10px 28px rgba(11,47,99,0.16); background-color:#ffffff; width:900px;margin-top:15px;margin-left:0px;margin-right:5px;font-size:13px;border:1px solid #d7e0ea;border-radius:8px;">
<div class="container">
<div class="header">
<div class="site-header-inner">
    <div class="site-logo"><img src="images/svxlinks5_logo_no_background.png" alt="SVXLink" /></div>
    <div class="site-title">
        <span class="callsign"><?php echo $callsign;?></span>
        <span class="network"><?php echo $fmnetwork; ?></span>
    </div>
    <div class="site-emblem"><strong>ZRS</strong>Zveza radioamaterjev Slovenije</div>
</div>
</div>
<?php include_once "include/top_menu.php"; ?>
<div class="content"><center>
<?php include "include/reflector_debug.php"; ?>
</center></div>
</div>
</fieldset>
</center>
</body>
</html>
