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

    // format: route|timestamp
    list($route, $timestamp) = explode("|", $decoded);

    // Expire after 10 minutes
    if (time() - $timestamp > 600) return false;

    return $route;
}


/* ============================================================
   📌 CLEAN URL ROUTES (PUBLIC ENTRY POINTS)
============================================================ */

$uri = strtok($_SERVER["REQUEST_URI"], "?"); // Remove query string

// ---------- Landing: /fiber ----------
if ($uri === "/fiber") {
    $token = encrypt_route("fiber");
    header("Location: /?v=$token");
    exit;
}

// ---------- /fiber/consent ----------
if ($uri === "/fiber/consent") {
    $token = encrypt_route("fiber_consent");
    header("Location: /?v=$token");
    exit;
}

// ---------- /fiber/register ----------
if ($uri === "/fiber/register") {
    $token = encrypt_route("fiber_register");
    header("Location: /?v=$token");
    exit;
}

// ---------- /fiber/chat/{ticket} ----------
if (preg_match("#^/fiber/chat/([0-9]+)$#", $uri, $match)) {

    $ticketId = $match[1];

    // encrypt: fiber_chat|8
    $token = encrypt_route("fiber_chat|" . $ticketId);

    header("Location: /?v=$token");
    exit;
}


/* ============================================================
   🎯 HANDLE ENCRYPTED ROUTING
============================================================ */

if (isset($_GET["v"])) {

    $route = decrypt_route($_GET["v"]);
    if (!$route) die("Invalid or expired token.");

    // ---------- CHAT ROUTE (fiber_chat|ticketId) ----------
    if (str_starts_with($route, "fiber_chat")) {

        // restore ticket ID
        list($label, $ticketId) = explode("|", $route);

        $_GET["ticket"] = $ticketId;

        require __DIR__ . "/SKYTRUFIBER/chat/chat_support.php";
        exit;
    }

    // ---------- STANDARD ROUTES ----------
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
            die("Unknown route: " . htmlspecialchars($route));
    }
}


/* ============================================================
   🏠 DEFAULT LANDING → GO TO DASHBOARD
============================================================ */

$token = encrypt_route("dashboard");
header("Location: /?v=$token");
exit;

?>
