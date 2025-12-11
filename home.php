<?php
/* ============================================================
   🔐 ENCRYPT / DECRYPT FUNCTIONS (same system as main site)
============================================================ */

function encrypt_route($route) {
    return urlencode(base64_encode($route . "|" . time()));
}

function decrypt_route($token) {

    $decoded = base64_decode($token);

    if (!$decoded || !str_contains($decoded, "|")) {
        return false;
    }

    list($route, $timestamp) = explode("|", $decoded);

    // Token expires after 10 minutes
    if (time() - $timestamp > 600) return false;

    return $route;
}

/* ============================================================
   🧭 CLEAN PUBLIC ROUTES
   /csr → redirects to encrypted login
============================================================ */

if (preg_match("#/csr$#", $_SERVER["REQUEST_URI"])) {

    // redirect to encrypted login
    $token = encrypt_route("csr_login");
    header("Location: /home.php?v=$token");
    exit;
}

/* ============================================================
   🎯 DECRYPTED ROUTES
============================================================ */

if (isset($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);

    if (!$route) {
        die("⛔ Invalid or expired access token.");
    }

    switch ($route) {
        case "csr_login":
            require __DIR__ . "/CSR/csr_login.php";
            exit;

        case "csr_dashboard":
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        default:
            die("⛔ Unknown encrypted route.");
    }
}

/* ============================================================
   🏠 If user directly visits home.php:
   send them to encrypted CSR login
============================================================ */

$token = encrypt_route("csr_login");
header("Location: /home.php?v=$token");
exit;
