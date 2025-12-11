<?php
// Start session ONLY if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ------------------------------------------
   CLEAR ALL SESSION DATA
------------------------------------------ */
$_SESSION = [];

/* ------------------------------------------
   DELETE SESSION COOKIE SAFELY
------------------------------------------ */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"] ?? '/',
        $params["domain"] ?? '',
        $params["secure"] ?? false,
        $params["httponly"] ?? true
    );
}

/* ------------------------------------------
   DESTROY THE SESSION COMPLETELY
------------------------------------------ */
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

/* ------------------------------------------
   REDIRECT TO CLEAN ROUTE
   /csr → home.php → encrypted csr_login
------------------------------------------ */
header("Location: /csr");
exit;
?>
