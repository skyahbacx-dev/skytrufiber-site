<?php
/* ============================================================
   🔐 ENCRYPT / DECRYPT SYSTEM
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
   🧭 CLEAN PUBLIC ACCESS ROUTES
   These URLs users can type safely
============================================================ */

/* 1️⃣ ahbadevt.com/csr → CSR login */
if (preg_match("#/csr$#", $_SERVER["REQUEST_URI"])) {

    session_start();
    if (isset($_SESSION["csr_user"])) {
        // If already logged in → go to encrypted dashboard
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* 2️⃣ ahbadevt.com/csr/dashboard → CSR dashboard */
if (preg_match("#/csr/dashboard$#", $_SERVER["REQUEST_URI"])) {

    session_start();
    if (!isset($_SESSION["csr_user"])) {
        // Not logged in → Force login
        $token = encrypt_route("csr_login");
    } else {
        $token = encrypt_route("csr_dashboard");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* ============================================================
   🎯 HANDLE DECRYPTED ROUTES
============================================================ */

if (isset($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);
    if (!$route) {
        die("⛔ Invalid or expired access token.");
    }

    session_start();

    switch ($route) {

        /* === CSR LOGIN === */
        case "csr_login":
            require __DIR__ . "/CSR/csr_login.php";
            exit;

        /* === CSR DASHBOARD (requires login) === */
        case "csr_dashboard":
            if (!isset($_SESSION["csr_user"])) {
                die("⛔ Unauthorized access.");
            }
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        default:
            die("⛔ Unknown encrypted route.");
    }
}

/* ============================================================
   🏠 Default action: redirect to CSR login
============================================================ */

$token = encrypt_route("csr_login");
header("Location: /home.php?v=$token");
exit;
