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
============================================================ */

$uri = strtok($_SERVER["REQUEST_URI"], "?");  // Remove query string
session_start();

/* -------------------------------
   1️⃣ /csr → CSR Login
-------------------------------- */
if (preg_match("#^/csr/?$#", $uri)) {

    if (isset($_SESSION["csr_user"])) {
        // Already logged in
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* -------------------------------
   2️⃣ /csr/dashboard → CSR Dashboard
-------------------------------- */
if (preg_match("#^/csr/dashboard/?$#", $uri)) {

    if (!isset($_SESSION["csr_user"])) {
        // No session → login first
        $token = encrypt_route("csr_login");
    } else {
        $token = encrypt_route("csr_dashboard");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* ============================================================
   🎯 HANDLE ENCRYPTED ROUTES
============================================================ */

if (isset($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);
    if (!$route) {
        die("⛔ Invalid or expired access token.");
    }

    switch ($route) {

        /* === CSR LOGIN PAGE === */
        case "csr_login":
            require __DIR__ . "/CSR/csr_login.php";
            exit;

        /* === CSR DASHBOARD PAGE (protected) === */
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
   🏠 Default → redirect to CSR login
============================================================ */

$token = encrypt_route("csr_login");
header("Location: /home.php?v=$token");
exit;
