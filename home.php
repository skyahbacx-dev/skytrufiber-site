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
   🧭 CLEAN CSR ROUTES (LINKS CLIENTS CAN TYPE)
============================================================ */

$uri = strtok($_SERVER["REQUEST_URI"], "?");

session_start();

/* 1️⃣ /csr → CSR LOGIN OR DASHBOARD */
if ($uri === "/csr") {

    if (!empty($_SESSION["csr_user"])) {
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* 2️⃣ /csr/dashboard */
if ($uri === "/csr/dashboard") {

    if (!empty($_SESSION["csr_user"])) {
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* 3️⃣ /csr/logout */
if ($uri === "/csr/logout") {
    $_SESSION = [];
    session_destroy();
    header("Location: /csr");
    exit;
}


/* ============================================================
   🎯 HANDLE ENCRYPTED ROUTES FROM ?v=
============================================================ */

if (isset($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);

    if (!$route) {
        die("⛔ Invalid or expired access token.");
    }

    switch ($route) {

        /* ----------------------------------------------
           CSR LOGIN PAGE
        ---------------------------------------------- */
        case "csr_login":
            require __DIR__ . "/CSR/csr_login.php";
            exit;

        /* ----------------------------------------------
           CSR DASHBOARD (default tab)
        ---------------------------------------------- */
        case "csr_dashboard":
            if (empty($_SESSION["csr_user"])) {
                die("⛔ Unauthorized access.");
            }

            $GLOBALS["CSR_TAB"] = "CHAT";

            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        /* ----------------------------------------------
           CSR – TAB: CHAT
        ---------------------------------------------- */
        case "csr_chat":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");

            $GLOBALS["CSR_TAB"] = "CHAT";

            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        /* ----------------------------------------------
           CSR – TAB: CLIENTS
        ---------------------------------------------- */
        case "csr_clients":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");

            $GLOBALS["CSR_TAB"] = "CLIENTS";

            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        /* ----------------------------------------------
           CSR – TAB: REMINDERS
        ---------------------------------------------- */
        case "csr_reminders":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");

            $GLOBALS["CSR_TAB"] = "REMINDERS";

            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        /* ----------------------------------------------
           CSR – TAB: SURVEY
        ---------------------------------------------- */
        case "csr_survey":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");

            $GLOBALS["CSR_TAB"] = "SURVEY";

            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        default:
            die("⛔ Unknown encrypted route: " . htmlspecialchars($route));
    }
}


/* ============================================================
   🏠 DEFAULT → CSR LOGIN
============================================================ */

$token = encrypt_route("csr_login");
header("Location: /home.php?v=$token");
exit;
