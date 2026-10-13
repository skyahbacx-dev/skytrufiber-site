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

/* ============================================================
   UPDATED: /fiber/register  
   ✔ Generates a real one-time token
   ✔ Stores token in session
   ✔ Prevents expired-key issues
============================================================ */
if ($uri === "/fiber/register") {

    session_start();

    // Generate one-time registration token
    $regToken = bin2hex(random_bytes(16));
    $_SESSION["registration_token"] = $regToken;

    // Encrypt route
    $token = encrypt_route("fiber_register");

    // Pass both tokens
    header("Location: /?v=$token&rt=$regToken");
    exit;
}

/* ============================================================
   NEW: SUCCESS PAGE ROUTE
   /fiber/register/success → encrypted success page
============================================================ */
if ($uri === "/fiber/register/success") {
    session_start();

    // generate one-time token for success page
    $successToken = bin2hex(random_bytes(16));
    $_SESSION["success_token"] = $successToken;

    $token = encrypt_route("fiber_register_success");
    header("Location: /?v=$token&st=$successToken");
    exit;
}

/* --------------------------
   /fiber/chat → encrypted chat route
--------------------------- */
if ($uri === "/fiber/chat") {

    parse_str($_SERVER["QUERY_STRING"] ?? '', $qs);

    $ticket = $qs["ticket"] ?? "";
    if ($ticket === "") {
        die("Missing ticket.");
    }

    $token = encrypt_route("fiber_chat");
    header("Location: /?v=$token&ticket=$ticket");
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

        case "fiber_register_success":
            require __DIR__ . "/SKYTRUFIBER/register_success.php";
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
