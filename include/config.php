<?php

if (!function_exists('parse_svxlink_config_file')) {
    function parse_svxlink_config_file($filename) {
        $config = array();
        $section = '';
        $lines = @file($filename, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return $config;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }

            if (preg_match('/^\[(.+)\]$/', $line, $matches)) {
                $section = trim($matches[1]);
                if (!isset($config[$section])) {
                    $config[$section] = array();
                }
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = array_map('trim', explode('=', $line, 2));
                $value = trim($value, "\"'");

                if ($section !== '') {
                    $config[$section][$key] = $value;
                } else {
                    $config[$key] = $value;
                }
            }
        }

        return $config;
    }
}

if ( file_exists(__DIR__."/config.inc.php") ) { include_once __DIR__."/config.inc.php"; }
else {
// header lines for information
define("HEADER_CAT","FM-Repeater");
define("HEADER_QTH",$qth);
define("HEADER_QRG",$freq);
define("HEADER_SYSOP","");
define("FMNETWORK_EXTRA","");
define("FULLACCESS_OUSIDE", "Full Access Outside");
define("EL_NODE_NR",$EL_node);
define("CALLSIGN","");
define("LOGICS","");
define("REPORT_CTCSS","");
define("DTMF_CTRL_PTY","");
define("API","");
define("FMNET","");
define("TG_URI","NULL");
define("NODE_INFO_FILE","");
define("RF_MODULE","");
define("GLOBAL","");
define("ReflectorLogic","");
define("LinkToReflector","");
define("Rx1","");
define("Tx1","");
define("Macros","");
define("LocationInfo","");
define("RepeaterLogic","");
define("SimplexLogic","");
define("KEY1", array('D1','D1#','green'));
define("KEY2", array('D2','D2#','orange'));
define("KEY3", array('D3','D3#','orange'));
define("KEY4", array('D4','D4#','orange'));
define("KEY5", array('D5', 'D5#','purple'));
define("KEY6", array('D6','D6#','purple'));
define("KEY7", array('D7','D7#','purple'));
define("KEY8", array('D8','D8#','blue'));
define("KEY9", array('D9','D9#','blue'));
define("KEY10", array('D10','D10#','red'));
// additional DTMF keys
define("KEY11", array('D11','D11#','green'));
define("KEY12", array('D12','D12#','orange'));
define("KEY13", array('D13','D13#','orange'));
define("KEY14", array('D14','D14#','orange'));
define("KEY15", array('D15','D15#','purple'));
define("KEY16", array('D16','D16#','purple'));
define("KEY17", array('D17','D17#','orange'));
define("KEY18", array('D18','D18#','blue'));
define("KEY19", array('D19','D19#','blue'));
define("KEY20", array('D20','D20#','red'));
        
define("SVXCONFPATH", "/etc/svxlink/");
define("SVXCONFIG", "svxlink.conf");
define("MODULEPATH", "/etc/svxlink/svxlink.d");
define("ECHOLINKCONFIG", "ModuleEchoLink.conf");
define("METARINFO", "ModuleMetarInfo.conf");

define("SVXLOGPATH", "/var/log/");
define("SVXLOGPREFIX","svxlink.log");
}
include_once "parse_svxconf.php";
        
error_reporting(0);
// Define name of your FM Network
define("FMNETWORK", $fmnetwork);
//
// Select only one URL for SVXReflector API to get connected Nodes
//
// FM SVXLink-UK
define("URLSVXRAPI", $refApi);
//
// Empty address API do not show connected nodes to svxreflector 
//define("URLSVXRAPI", "");
//
// Put url address to your svxreflector which offer information of status
//define("URLSVXRAPI", "http://192.168.1.33:9999/status");
//
//
// Orange Pi Zero LTS version requires CPU_TEMP_OFFSET value 30 
// to display CPU TEMPERATURE correctly
define("CPU_TEMP_OFFSET","0");
//
// Define where is located menu wit buttons TOP or BOTTOM
define("MENUBUTTON", "BOTTOM");
//
// Button keys define: description button, DTMF command or command, color of button
//
// DTMF keys
// syntax: 'KEY number,'Description','DTMF code','color button' 
//
//
// command "shutdown now" 
//
// Set SHOWPTT to TRUE if you want use microphone connected
// to sound card and use buttons on dashboard PTT ON & PTT OFF
// Set SHOWPTT to FALSE to disable display PTT buttons
// In most cases you can switch to FALSE
define("SHOWPTT","TRUE");
//
//
$svxConfigFile = '/etc/svxlink/svxlink.conf';
if (fopen($svxConfigFile,'r'))
   { $svxconfig = parse_svxlink_config_file($svxConfigFile);
     $refApi = $svxconfig['ReflectorLogic']['API'];
     $fmnetwork =$svxconfig['ReflectorLogic']['HOSTS'];
     $qth = $svxconfig['LocationInfo']['QTH'];
     $freq = $svxconfig['Rx1']['RX'];
    $EL_node = $svxconfig['LocationInfo']['LOCATION'];
    }
else { $callsign="NOCALL";
   $fmnetwork="no registered";
    }

