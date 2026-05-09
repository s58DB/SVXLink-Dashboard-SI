<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . "/auth.php";
?>
   <div id="display-links">
	<p>
	<a>Pregled</a> |
	<a href="/index.php">Nadzorna plosca</a> |
	<a href="/node.php">Vozlisca</a> |
	<a href="/tg.php">Talk Groups</a> |
	<a href="/dtmf.php">DTMF</a> |
	<a href="/audio.php">Audio</a> |
<?php if (isAuthorised()) { ?>
	<a href="editor.php?id=log" class="nav-alert" id="log">Log</a> |
<?php } ?>
	<a href="/authorise.php" class="nav-alert"><?php echo isAuthorised() ? "Avtoriziran" : "Prijava"; ?></a></p>
	</div>
<?php if (isAuthorised()) { ?>
	<div id="full-edit-links">
	<p><a>Urejanje</a> |
	<a href="/editor.php?id=svxlink" class="nav-alert" id="svxlink">SVXLink</a> |
	<a href="/editor.php?id=talkgroups" class="nav-alert" id="talkgroups">Talkgroups</a> |
	<a href="/editor.php?id=buttons" class="nav-alert" id="buttons">Gumbi</a> |
	<a href="/editor.php?id=amixer" class="nav-alert" id="amixer">Amixer</a> |
	<a href="/editor.php?id=echolink" class="nav-alert" id="echolink">EchoLink</a> |
	<a href="/editor.php?id=metarinfo" class="nav-alert" id="metarinfo">MetarInfo</a> |
	<a href="/editor.php?id=nodeInfo" class="nav-alert" id="nodeInfo">NodeInfo</a> |
	<a href="/editor.php?id=power">Napajanje</a></p>
    </div>
<?php } else { ?>
	<div id="full-edit-links">
	<p><a>Urejanje</a> | <span class="nav-locked">Za urejanje, log in napajanje se morate prijaviti.</span></p>
    </div>
<?php } ?>

	 



<?php

include_once('parse_svxconf.php');


/*if (fopen($svxConfigFile,'r'))
{

  $svxconfig = parse_ini_file($svxConfigFile,true,INI_SCANNER_RAW);
  $logics = explode(",",$svxconfig['GLOBAL']['LOGICS']);
  foreach ($logics as $key) {
	if ($key == "SimplexLogic") $isSimplex = true;
	if ($key == "RepeaterLogic") $isRepeater = true; 
  };
  $logics = explode(",",$svxconfig['GLOBAL']['LOGICS']);
  if ($isSimplex) $modules = explode(",",str_replace('Module','',$svxconfig['SimplexLogic']['MODULES']));
  if ($isRepeater) $modules = explode(",",str_replace('Module','',$svxconfig['RepeaterLogic']['MODULES']));
  foreach ($modules as $key){
	if ($key == "EchoLink") $isEchoLink = true;
 }
 */
 //if ($isEchoLink==true) {echo ' <a href="/echolink.php" style = "color: #0000ff;">EchoLink</a> |';};
//$globalRf = $svxconfig['GLOBAL']['RF_MODULE'];

/*if ($globalRf <> "No")
{
	echo'	<a href="/rf.php" style = "color: #0000ff;"> Rf</a> |';
}
}*/
?>
