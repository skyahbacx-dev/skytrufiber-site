<?php
session_start();

/* ------------------------------------------
   CLEAR ALL SESSION DATA
------------------------------------------ */
$_SESSION = [];

/* Delete session cookie */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* Destroy the session completely */
session_destroy();

/* ------------------------------------------
   REDIRECT USING CLEAN ROUTE
   /csr → home.php → encrypted csr_login
------------------------------------------ */
header("Location: /csr");
exit;
?>
