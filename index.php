<?php
// If encrypted token ?v= exists → decrypt and route
if (isset($_GET['v'])) {

    $decoded = base64_decode($_GET['v']);

    // Format should be: dashboard|timestamp
    list($route, $timestamp) = explode("|", $decoded);

    // Optional security: token expires after 5 minutes
    if (time() - $timestamp > 300) {
        die("Token expired.");
    }

    // Route to dashboard
    if ($route === "dashboard") {
        require __DIR__ . "/dashboard/dashboard.php";
        exit;
    }
}

// Default behavior (no token)
$token = base64_encode("dashboard|" . time());
header("Location: ?v=" . urlencode($token));
exit;
