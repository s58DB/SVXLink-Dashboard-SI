<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once "config.php";         
include_once "tools.php";        
include_once "functions.php";    
include_once "tgdb.php";
include_once __DIR__ . "/tgdb_edit.php";
$svxConfigFile = '/etc/svxlink/svxlink.conf';
    if (is_readable($svxConfigFile))
       { $svxconfig = parse_ini_file($svxConfigFile,true,INI_SCANNER_RAW);  
        $callsign = $svxconfig['ReflectorLogic']['CALLSIGN'];
        $fmnetwork =$svxconfig['ReflectorLogic']['HOSTS'];
        //$tgUri = $svxconfig['ReflectorLogic']['TG_URI'];
}

$authorised = isAuthorised();
$tgMessage = "";
$tgError = "";

if (isset($_POST['btnUpdateTgs']))
    {

        $retval = null;
        $screen = null;
        //$sAconn = $_POST['sAconn'];
        //$password = $_POST['password'];
        //exec('sudo -n nmcli dev wifi rescan');
        $command = "sudo wget ".$tgUri." 2>&1";
        exec($command,$screen,$retval);
	//if ($retval) {
	//echo "*";
	$command2 = "sudo mv /var/www/html/tgdb.txt /var/www/html/include/tgdb.php 2>&1";
        exec($command2,$screen,$retval);
	//}
        //$_SESSION['refresh']=True; header("Refresh: 3");

};

if (isset($_POST['save_tgdb']) && !$authorised) {
    $tgError = "Niste avtorizirani za spreminjanje TG baze.";
}

if (isset($_POST['save_tgdb']) && $authorised) {
    $updatedTgs = array();
    foreach ($_POST['tg_names'] ?? array() as $tg => $tgname) {
        $tg = normalizeTgNumber($tg);
        $tgname = normalizeTgName($tgname);
        if ($tg !== "" && $tgname !== "") {
            $updatedTgs[$tg] = $tgname;
        }
    }

    $newTg = normalizeTgNumber($_POST['new_tg'] ?? "");
    $newName = normalizeTgName($_POST['new_tg_name'] ?? "");
    if ($newTg !== "" && $newName !== "") {
        $updatedTgs[$newTg] = $newName;
    }

    if (saveTgDb($updatedTgs, $tgError)) {
        $tgdb_array = sortTgDb($updatedTgs);
        $tgMessage = "TG baza je shranjena. Stolpec TG Name v SVXReflector Activity uporablja ta ista imena.";
    }
}

if (isset($_POST['delete_tg']) && !$authorised) {
    $tgError = "Niste avtorizirani za brisanje TG iz baze.";
}

if (isset($_POST['delete_tg']) && $authorised) {
    $deleteTg = normalizeTgNumber($_POST['delete_tg']);
    if ($deleteTg !== "" && isset($tgdb_array[$deleteTg])) {
        unset($tgdb_array[$deleteTg]);
        if (saveTgDb($tgdb_array, $tgError)) {
            $tgdb_array = sortTgDb($tgdb_array);
            $tgMessage = "TG " . htmlspecialchars($deleteTg, ENT_QUOTES, 'UTF-8') . " je odstranjen iz baze.";
        }
    }
}

?>
<span style = "font-weight: bold;font-size:14px;">Talk Groups</span>
<fieldset style = " width:550px;box-shadow:5px 5px 20px #999;background-color:#e8e8e8e8;margin-top:10px;margin-left:0px;margin-right:0px;font-size:12px;border-top-left-radius: 10px; border-top-right-radius: 10px;border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;">
<?php
if ($tgMessage !== "") {
    echo '<div class="auth-warning" style="background:#e8f6ec;color:#1f6b34;border-color:#9cc9a8;">' . $tgMessage . '</div>';
}
if ($tgError !== "") {
    echo '<div class="auth-warning">' . htmlspecialchars($tgError, ENT_QUOTES, 'UTF-8') . '</div>';
}
if (!$authorised) {
    renderUnauthorisedMessage("Za urejanje TG imen se prijavite kot sysop. Branje in M/A gumbi ostanejo na voljo glede na obstojece pravice.");
}
?>
  <form method="post">
  <table style = "margin-top:0px;">
    <tr height=25px>
      <th width=100px>TG #</th>
      <th width=30px> M </th>
      <th width=30px> A </th>
      <th>TG Name</th>
<?php if ($authorised) { ?>
      <th width=70px>Uredi</th>
<?php } ?>
    </tr>
<?php
foreach (sortTgDb($tgdb_array) as $tg => $tgname)
{ 
        $safeTg = htmlspecialchars($tg, ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($tgname, ENT_QUOTES, 'UTF-8');
		echo "<tr>";
		echo "<td align=\"left\">&nbsp;<span style=\"color:#b5651d;font-weight:bold;\">$safeTg</span></td>";
        if (ctype_digit((string)$tg)) {
		    echo "<td><button type=submit id=jumptoM name=jmptoM class=monitor_id value=\"$safeTg\"><i class=\"material-icons\" style=\"font-size:15px;\">volume_up</i></button></td>";
            echo "<td><button type=submit id=jumptoA name=jmptoA class=active_id value=\"$safeTg\"><i class=\"material-icons\" style=\"font-size:15px;\">cell_tower</i></button></td>";
        } else {
            echo "<td></td><td></td>";
        }
        if ($authorised && ctype_digit((string)$tg)) {
		    echo "<td><input type=\"text\" name=\"tg_names[$safeTg]\" value=\"$safeName\" style=\"width:230px;font-size:12px;\" /></td>";
            echo "<td><button type=\"submit\" name=\"delete_tg\" value=\"$safeTg\" class=\"red\" style=\"height:24px;width:58px;font-size:11px;\">Delete</button></td>";
        } else {
		    echo "<td style=\"font-weight:bold;color:#464646;\">&nbsp;<b>".$safeName."</b></td>";
            if ($authorised) {
                echo "<td></td>";
            }
        }
		echo"</tr>\n";
};

if ($authorised) {
    echo '<tr height="32px">';
    echo '<td><input type="text" name="new_tg" placeholder="TG" style="width:70px;font-size:12px;" /></td>';
    echo '<td></td><td></td>';
    echo '<td><input type="text" name="new_tg_name" placeholder="Novo TG ime" style="width:230px;font-size:12px;" /></td>';
    echo '<td></td>';
    echo '</tr>';
}
?>
  </table>
<?php if ($authorised) { ?>
<button name="save_tgdb" type="submit" class="green" style = "height:30px; width:120px; font-size:12px;">Shrani TG</button>
<?php } ?>
<!--<button name="btnUpdateTgs" type="submit" class="red" style = "height:30px; width:120px; font-size:12px;">Update Tgs</button>-->
</form>
</fieldset>
