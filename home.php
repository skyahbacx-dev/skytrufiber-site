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
   🧭 CLEAN CSR ROUTES (PUBLIC ENTRY POINTS)
============================================================ */

session_start();
$uri = strtok($_SERVER["REQUEST_URI"], "?");

/* 1️⃣ /csr → LOGIN or DASHBOARD */
if ($uri === "/csr") {

    if (!empty($_SESSION["csr_user"])) {
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* 2️⃣ /csr/dashboard → Always Dashboard */
if ($uri === "/csr/dashboard") {

    if (!empty($_SESSION["csr_user"])) {
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* 3️⃣ /csr/logout → Proper Logout */
if ($uri === "/csr/logout") {

    $_SESSION = [];
    if (session_id()) session_destroy();

    header("Location: /csr");
    exit;
}


/* ============================================================
   🎯 HANDLE ENCRYPTED ROUTES (?v=TOKEN)
============================================================ */

if (isset($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);

    if (!$route) {
        die("⛔ Invalid or expired access token.");
    }

    switch ($route) {

        /* CSR Login */
        case "csr_login":
            require __DIR__ . "/CSR/csr_login.php";
            exit;

        /* CSR Dashboard (default tab = chat) */
        case "csr_dashboard":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized access.");
            $GLOBALS["CSR_TAB"] = "chat";
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        /* CSR → Chat Tab */
        case "csr_chat":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");
            $GLOBALS["CSR_TAB"] = "chat";
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        /* CSR → Clients Tab */
        case "csr_clients":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");
            $GLOBALS["CSR_TAB"] = "clients";
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        /* CSR → Reminders Tab */
        case "csr_reminders":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");
            $GLOBALS["CSR_TAB"] = "reminders";
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        /* CSR → Survey Tab */
        case "csr_survey":
            if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized.");
            $GLOBALS["CSR_TAB"] = "survey";
            require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
            exit;

        default:
            die("⛔ Unknown encrypted route: " . htmlspecialchars($route));
    }
}


/* ============================================================
   🏠 DEFAULT → ALWAYS redirect to clean route /csr
   (This prevents falling back to any other router)
============================================================ */

header("Location: /csr");
exit;
