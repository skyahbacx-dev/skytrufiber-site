<?php
/* ============================================================
   🔐 ENCRYPT / DECRYPT SYSTEM
============================================================ */
function encrypt_route($route) {
    return urlencode(base64_encode($route . "|" . time()));
}

function decrypt_route($token) {
    $decoded = base64_decode($token);
    if (!$decoded || !str_contains($decoded, "|")) return false;

    list($route, $timestamp) = explode("|", $decoded);

    // Token is valid for 10 minutes
    if (time() - $timestamp > 600) return false;

    return $route;
}

/* ============================================================
   🧭 CLEAN PUBLIC CSR ROUTES
   These URLs users can enter directly
============================================================ */

/* /csr → CSR login OR CSR dashboard */
if (preg_match("#^/csr/?$#", $_SERVER["REQUEST_URI"])) {
    session_start();

    $token = encrypt_route(
        isset($_SESSION["csr_user"]) ? "csr_dashboard" : "csr_login"
    );

    header("Location: /home.php?v=$token");
    exit;
}

/* /csr/dashboard → CSR dashboard (must be logged in) */
if (preg_match("#^/csr/dashboard/?$#", $_SERVER["REQUEST_URI"])) {
    session_start();

    $token = encrypt_route(
        isset($_SESSION["csr_user"]) ? "csr_dashboard" : "csr_login"
    );

    header("Location: /home.php?v=$token");
    exit;
}

/* /csr/logout → logout and redirect to login */
if (preg_match("#^/csr/logout/?$#", $_SERVER["REQUEST_URI"])) {
    session_start();
    session_destroy();
    header("Location: /csr");
    exit;
}

/* ============================================================
   🎯 DECRYPT ROUTE & LOAD THE CORRECT CSR FILE
============================================================ */
if (isset($_GET["v"])) {

    session_start();
    $route = decrypt_route($_GET["v"]);

    if (!$route) {
        die("⛔ Invalid or expired CSR access token.");
    }

    switch ($route) {

        /* CSR LOGIN PAGE */
        case "csr_login":
            require __DIR__ . "/CSR/csr_login.php";
            exit;

        /* CSR DASHBOARD PAGE */
        case "csr_dashboard":
            if (!isset($_SESSION["csr_user"])) {
                die("⛔ Access denied. You are not logged in.");
            }
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        default:
            die("⛔ Unknown CSR route.");
    }
}

/* ============================================================
   🏠 DEFAULT FALLBACK
   If route did not match → go to CSR login
============================================================ */
$token = encrypt_route("csr_login");
header("Location: /home.php?v=$token");
exit;
?>
