<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function normalizeTgNumber($tg) {
    $tg = trim((string)$tg);
    return preg_match('/^[0-9]+$/', $tg) ? $tg : "";
}

function normalizeTgName($name) {
    $name = trim((string)$name);
    return preg_replace('/\s+/', ' ', $name);
}

function sortTgDb($tgdb) {
    uksort($tgdb, function($a, $b) {
        $aNumeric = ctype_digit((string)$a);
        $bNumeric = ctype_digit((string)$b);

        if ($aNumeric && $bNumeric) {
            return (int)$a <=> (int)$b;
        }

        if ($aNumeric) {
            return -1;
        }

        if ($bNumeric) {
            return 1;
        }

        return strcasecmp((string)$a, (string)$b);
    });

    return $tgdb;
}

function saveTgDb($tgdb, &$error = "") {
    $tgdb = sortTgDb($tgdb);
    $target = __DIR__ . "/tgdb.php";
    $export = var_export($tgdb, true);
    $content = "<?php\n"
        . "if (session_status() === PHP_SESSION_NONE) {\n"
        . "    session_start();\n"
        . "}\n"
        . "/* talkgroup / number alias database */\n"
        . "\$tgdb_array = " . $export . ";\n"
        . "?>\n";

    if (file_put_contents($target, $content, LOCK_EX) === false) {
        $error = "TG baze ni bilo mogoce zapisati. Preverite dovoljenja za include/tgdb.php.";
        return false;
    }

    return true;
}
?>
