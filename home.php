<?php
/* ============================================================
   SESSION — must be first
============================================================ */
session_start();

/* ============================================================
   🔥 BLOCK DIRECT ACCESS TO /home.php
   (Prevents public homepage from loading)
============================================================ */
if (!isset($_GET["v"])) {
    header("Location: /csr");
    exit;
}

/* ============================================================
   🔐 ENCRYPTION / DECRYPTION
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
    if (time() - $timestamp > 600) {
        return false;
    }

    return $route;
}

/* ============================================================
   🧭 PUBLIC ENTRY ROUTER (Handles /csr, /csr/dashboard, /csr/logout)
============================================================ */

$uri = strtok($_SERVER["REQUEST_URI"], "?");

/* /csr → Login or Dashboard */
if ($uri === "/csr") {

    if (!empty($_SESSION["csr_user"])) {
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* /csr/dashboard */
if ($uri === "/csr/dashboard") {

    if (!empty($_SESSION["csr_user"])) {
        $token = encrypt_route("csr_dashboard");
    } else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* /csr/logout */
if ($uri === "/csr/logout") {

    $_SESSION = [];
    
    if (session_id()) {
        session_destroy();
    }

    // ALWAYS return to clean login route
    header("Location: /csr");
    exit;
}


/* ============================================================
   🎯 HANDLE ENCRYPTED ROUTES (?v=TOKEN)
============================================================ */

$route = decrypt_route($_GET["v"]);

if (!$route) {
    die("⛔ Invalid or expired access token.");
}

switch ($route) {

    /* CSR Login */
    case "csr_login":
        require __DIR__ . "/CSR/csr_login.php";
        exit;

    /* CSR Dashboard */
    case "csr_dashboard":
        if (empty($_SESSION["csr_user"])) die("⛔ Unauthorized access.");
        $GLOBALS["CSR_TAB"] = "CHAT";
        require __DIR__ . "/CSR/dashboard/csr_dashboard.php";
        exit;

    /* Tabs */
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

    default:
        die("⛔ Unknown encrypted route: " . htmlspecialchars($route));
}

/* ============================================================
   🚫 SHOULD NEVER REACH HERE
============================================================ */
header("Location: /csr");
exit;

?>
