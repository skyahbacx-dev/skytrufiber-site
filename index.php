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

    // token expires in 10 minutes
    if (time() - $timestamp > 600) return false;

    return $route;
}

/* ============================================================
   📌 CLEAN URL ROUTES (PUBLIC ENTRY POINTS)
============================================================ */

$uri = strtok($_SERVER["REQUEST_URI"], "?"); // remove query string

/* --------------------------
   SKYTRUFIBER ROUTES
--------------------------- */

// /fiber → encrypted skytrufiber portal
if ($uri === "/fiber") {
    $token = encrypt_route("fiber");
    header("Location: /?v=$token");
    exit;
}

// /fiber/consent → encrypted consent page
if ($uri === "/fiber/consent") {
    $token = encrypt_route("fiber_consent");
    header("Location: /?v=$token");
    exit;
}

// /fiber/register → encrypted register page
if ($uri === "/fiber/register") {
    $token = encrypt_route("fiber_register");
    header("Location: /?v=$token");
    exit;
}

/* --------------------------
   NEW FIX:
   /fiber/chat → encrypted chat route
--------------------------- */
if ($uri === "/fiber/chat") {

    // Pass ticket safely
    $ticket = $_GET["ticket"] ?? '';

    $token = encrypt_route("fiber_chat");

    header("Location: /?v=$token&ticket=" . urlencode($ticket));
    exit;
}

/* ============================================================
   🎯 HANDLE ENCRYPTED ROUTING
============================================================ */
if (isset($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);
    if (!$route) die("Invalid or expired token.");

    switch ($route) {

        case "dashboard":
            require __DIR__ . "/dashboard/dashboard.php";
            exit;

        case "fiber":
            require __DIR__ . "/SKYTRUFIBER/skytrufiber.php";
            exit;

        case "fiber_consent":
            require __DIR__ . "/SKYTRUFIBER/consent.php";
            exit;

        case "fiber_register":
            require __DIR__ . "/SKYTRUFIBER/register.php";
            exit;

        case "fiber_chat":
            require __DIR__ . "/SKYTRUFIBER/chat/chat_support.php";
            exit;

        default:
            die("Unknown route: " . htmlspecialchars($route));
    }
}

/* ============================================================
   🏠 DEFAULT LANDING → ALWAYS ENCRYPT DASHBOARD
============================================================ */
$token = encrypt_route("dashboard");
header("Location: /?v=$token");
exit;

