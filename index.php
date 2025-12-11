<?php

/* ============================================================
   🔐 ENCRYPT / DECRYPT HELPERS
============================================================ */
function encrypt_route($route) {
    return urlencode(base64_encode($route . "|" . time()));
}

function decrypt_route($token) {
    $decoded = base64_decode($token);
    if (!$decoded || !str_contains($decoded, "|")) return false;

    list($route, $timestamp) = explode("|", $decoded);

    // Token expires in 10 minutes
    if (time() - $timestamp > 600) return false;

    return $route;
}

/* ============================================================
   📌 CLEAN URL ROUTES → USER FRIENDLY ENTRY POINTS
============================================================ */

$uri = strtok($_SERVER["REQUEST_URI"], "?"); // remove query string safely

// Ensure trailing slash does not break routing
$uri = rtrim($uri, "/");

switch ($uri) {

    case "/fiber":
        header("Location: /?v=" . encrypt_route("fiber"));
        exit;

    case "/fiber/consent":
        header("Location: /?v=" . encrypt_route("fiber_consent"));
        exit;

    case "/fiber/register":
        header("Location: /?v=" . encrypt_route("fiber_register"));
        exit;

    case "/fiber/chat":
        header("Location: /?v=" . encrypt_route("fiber_chat"));
        exit;

}

/* ============================================================
   🎯 HANDLE ENCRYPTED TOKEN ROUTING
============================================================ */
if (isset($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);

    if (!$route) {
        die("Invalid or expired encrypted token.");
    }

    switch ($route) {

        /* Dashboard */
        case "dashboard":
            require __DIR__ . "/dashboard/dashboard.php";
            exit;

        /* SkyTruFiber Login Page */
        case "fiber":
            require __DIR__ . "/SKYTRUFIBER/skytrufiber.php";
            exit;

        /* Consent Page */
        case "fiber_consent":
            require __DIR__ . "/SKYTRUFIBER/consent.php";
            exit;

        /* Registration Page */
        case "fiber_register":
            require __DIR__ . "/SKYTRUFIBER/register.php";
            exit;

        /* Chat UI Page */
        case "fiber_chat":
            require __DIR__ . "/SKYTRUFIBER/chat/chat_support.php";
            exit;

        default:
            die("Unknown encrypted route: " . htmlspecialchars($route));
    }
}

/* ============================================================
   🏠 DEFAULT LANDING → ENCRYPTED DASHBOARD REDIRECT
============================================================ */
header("Location: /?v=" . encrypt_route("dashboard"));
exit;
