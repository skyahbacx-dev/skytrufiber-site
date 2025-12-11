<?php

/* ============================================================
   🔐 ENCRYPT / DECRYPT
============================================================ */
function encrypt_route($route) {
    return urlencode(base64_encode($route . "|" . time()));
}

function decrypt_route($token) {
    $decoded = base64_decode($token);
    if (!$decoded || !str_contains($decoded, "|")) return false;

    list($route, $timestamp) = explode("|", $decoded);

    // Token expires after 10 minutes
    if (time() - $timestamp > 600) return false;

    return $route;
}

/* ============================================================
   📌 CLEAN URL ROUTES
============================================================ */

// /fiber → encrypted skytrufiber portal
if (preg_match("#^/fiber$#", $_SERVER["REQUEST_URI"])) {
    $token = encrypt_route("fiber");
    header("Location: /?v=$token");
    exit;
}

// /fiber/consent → encrypted consent page
if (preg_match("#^/fiber/consent$#", $_SERVER["REQUEST_URI"])) {
    $token = encrypt_route("fiber_consent");
    header("Location: /?v=$token");
    exit;
}

// /fiber/register → encrypted register page
if (preg_match("#^/fiber/register$#", $_SERVER["REQUEST_URI"])) {
    $token = encrypt_route("fiber_register");
    header("Location: /?v=$token");
    exit;
}


/* ============================================================
   🎯 HANDLE ENCRYPTED TOKEN ROUTES
============================================================ */
if (isset($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);
    if (!$route) die("Invalid or expired token");

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

        default:
            die("Unknown route.");
    }
}

/* ============================================================
   🏠 DEFAULT LANDING → ENCRYPTED DASHBOARD
============================================================ */
$token = encrypt_route("dashboard");
header("Location: /?v=$token");
exit;
