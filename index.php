<?php

/* ============================================================
   🔐 ENCRYPT / DECRYPT FUNCTIONS
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

    // token expires after 10 minutes
    if (time() - $timestamp > 600) return false;

    return $route;
}

/* ============================================================
   📌 CLEAN ACCESS — PRETTY URL SHORTCUTS
============================================================ */

// /fiber → encrypted login page
if (preg_match("#/fiber$#", $_SERVER["REQUEST_URI"])) {
    $token = encrypt_route("fiber_login");
    header("Location: /?v=$token");
    exit;
}

/* ============================================================
   🎯 ENCRYPTED TOKEN ROUTES
============================================================ */

if (isset($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);

    if (!$route) {
        die("Invalid or expired access token.");
    }

    switch ($route) {

        case "dashboard":
            require __DIR__ . "/dashboard/dashboard.php";
            exit;

        /* SKYTRUFIBER ROUTES */
        case "fiber_login":
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
   🏠 DEFAULT ROUTE — Always send encrypted dashboard
============================================================ */
$token = encrypt_route("dashboard");
header("Location: /?v=$token");
exit;
