<?php
/* ============================================================
   🔐 ENCRYPT / DECRYPT SYSTEM
============================================================ */

/* ❗ FIX: Use separate session name for CSR system */
ini_set("session.name", "CSRSESSID");
session_start();

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
   🧭 CSR PUBLIC ROUTES (NO LOOPS, NO FALLBACK)
============================================================ */

$uri = strtok($_SERVER["REQUEST_URI"], "?");


/* 1️⃣ /csr → redirect to login or dashboard */
if ($uri === "/csr") {

    if (!empty($_SESSION["csr_user"])) {
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}


/* 2️⃣ /csr/logout → clear only CSR session */
if ($uri === "/csr/logout") {

    $_SESSION = [];
    session_destroy();

    $token = encrypt_route("csr_login");
    header("Location: /home.php?v=$token");
    exit;
}


/* 3️⃣ /csr/dashboard → no loops */
if ($uri === "/csr/dashboard") {

    if (!empty($_SESSION["csr_user"])) {
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}


/* ============================================================
   🧭 CLEAN, BOOKMARKABLE ROUTES (?tab=dashboard|chat|customers|...)
   ------------------------------------------------------------
   Added alongside the older ?v= token scheme below - nothing about
   the old links changes or breaks. The old token never actually
   encoded anything sensitive (it's a tab name + timestamp, expiring
   after 10 minutes), so this is a UX fix, not a security change:
   it just lets a tab be a real, bookmarkable/back-button-friendly URL.
============================================================ */

$cleanTabMap = [
    "dashboard"        => "DASHBOARD",
    "chat"             => "CHAT",
    "customers"        => "CLIENTS",
    "customer"         => "CUSTOMER",
    "reminders"        => "REMINDERS",
    "survey"           => "SURVEY",
    "survey_analytics" => "SURVEY_ANALYTICS",
];

if (!empty($_GET["tab"]) && isset($cleanTabMap[$_GET["tab"]])) {
    if (empty($_SESSION["csr_user"])) {
        header("Location: /home.php?tab=login");
        exit;
    }
    $GLOBALS["CSR_TAB"] = $cleanTabMap[$_GET["tab"]];
    require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
    exit;
}

if (($_GET["tab"] ?? null) === "login") {
    require __DIR__ . "/CSR/csr_login.php";
    exit;
}

/* ============================================================
   🎯 HANDLE ENCRYPTED ROUTES (?v=TOKEN)
============================================================ */

if (!empty($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);

    if (!$route) {
        die("⛔ Invalid or expired access token.");
    }

    switch ($route) {

        case "csr_login":
            require __DIR__ . "/CSR/csr_login.php";
            exit;

        case "csr_dashboard":
            if (empty($_SESSION["csr_user"])) {
                $token = encrypt_route("csr_login");
                header("Location: /home.php?v=$token");
                exit;
            }

            $GLOBALS["CSR_TAB"] = "DASHBOARD";
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        case "csr_chat":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");
            $GLOBALS["CSR_TAB"] = "CHAT";
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        case "csr_clients":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");
            $GLOBALS["CSR_TAB"] = "CLIENTS";
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        case "csr_reminders":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");
            $GLOBALS["CSR_TAB"] = "REMINDERS";
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        case "csr_survey":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");
            $GLOBALS["CSR_TAB"] = "SURVEY";
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        case "csr_survey_analytics":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");
            $GLOBALS["CSR_TAB"] = "SURVEY_ANALYTICS";
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        default:
            die("⛔ Unknown encrypted route");
    }
}


/* ============================================================
   🏁 DEFAULT → send to CSR login
============================================================ */

$token = encrypt_route("csr_login");
header("Location: /home.php?v=$token");
exit;

?>
