<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('isAuthorised')) {
    function isAuthorised() {
        return isset($_SESSION['auth']) && $_SESSION['auth'] === 'AUTHORISED';
    }
}

if (!function_exists('renderUnauthorisedMessage')) {
    function renderUnauthorisedMessage($message = "Niste avtorizirani za spremembe. Prijavite se z uporabniskim imenom in geslom sysopa.") {
        echo '<div class="auth-warning">' . htmlspecialchars($message, ENT_QUOTES) . '</div>';
    }
}
?>
