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
   🧭 CSR CLEAN ROUTES
   These URLs users can type directly
============================================================ */

$uri = strtok($_SERVER["REQUEST_URI"], "?");

/* 1️⃣ /csr → Load CSR login */
if ($uri === "/csr") {

    session_start();

    // If logged in → skip to dashboard
    if (!empty($_SESSION["csr_user"])) {
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* 2️⃣ /csr/dashboard → Dashboard */
if ($uri === "/csr/dashboard") {

    session_start();

    if (!empty($_SESSION["csr_user"])) {
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* 3️⃣ /csr/logout → proper logout */
if ($uri === "/csr/logout") {
    session_start();
    session_destroy();
    header("Location: /csr");
    exit;
}


/* ============================================================
   🎯 HANDLE DECRYPTED ROUTES FROM ?v=
============================================================ */

if (isset($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);

    if (!$route) {
        die("⛔ Invalid or expired access token.");
    }

    session_start();

    switch ($route) {

        /* CSR LOGIN */
        case "csr_login":
            require __DIR__ . "/CSR/csr_login.php";
            exit;

        /* CSR DASHBOARD (requires login) */
        case "csr_dashboard":
            if (empty($_SESSION["csr_user"])) {
                die("⛔ Unauthorized access.");
            }
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        default:
            die("⛔ Unknown encrypted route.");
    }
}


/* ============================================================
   🏠 DEFAULT: Always go to CSR login
============================================================ */
$token = encrypt_route("csr_login");
header("Location: /home.php?v=$token");
exit;
