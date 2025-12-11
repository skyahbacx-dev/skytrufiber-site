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
   🧭 CSR PUBLIC ROUTES (NO LOOPS, NO FALLBACK)
============================================================ */

session_start();
$uri = strtok($_SERVER["REQUEST_URI"], "?");

/* ------------------------------------------
   1️⃣  /csr  → Always handled here
------------------------------------------ */
if ($uri === "/csr") {

    // If logged in → go to dashboard
    if (!empty($_SESSION["csr_user"])) {
        $token = encrypt_route("csr_dashboard");
    } 
    // If not logged in → go to login
    else {
        $token = encrypt_route("csr_login");
    }

    header("Location: /home.php?v=$token");
    exit;
}

/* ------------------------------------------
   2️⃣  /csr/logout (reset session)
------------------------------------------ */
if ($uri === "/csr/logout") {

    $_SESSION = [];
    session_destroy();

    // ALWAYS send user to CSR login
    $token = encrypt_route("csr_login");
    header("Location: /home.php?v=$token");
    exit;
}

/* ------------------------------------------
   3️⃣  /csr/dashboard (never loop)
------------------------------------------ */
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
                // Not logged in → force login
                $token = encrypt_route("csr_login");
                header("Location: /home.php?v=$token");
                exit;
            }

            $GLOBALS["CSR_TAB"] = "CHAT";
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
   🏁 NO ROUTE MATCHED → ALWAYS GO TO CSR LOGIN
============================================================ */

$token = encrypt_route("csr_login");
header("Location: /home.php?v=$token");
exit;

?>
