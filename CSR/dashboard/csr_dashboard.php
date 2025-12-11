<?php


if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;

// History parameters (still valid inside dashboard)
$clientID = intval($_GET["client"] ?? 0);
$ticketID = intval($_GET["ticket"] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSR Dashboard — <?= htmlspecialchars($csrFullName) ?></title>

<!-- CSS -->
<link rel="stylesheet" href="/CSR/dashboard/csr_dashboard.css">
<link rel="stylesheet" href="/CSR/chat/chat.css?v=3">
<link rel="stylesheet" href="/CSR/history/history.css?v=3">

<!-- FontAwesome -->
<link rel="stylesheet" 
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- JS LIBRARIES -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Sortable -->
<script src="/CSR/vendor/js/Sortable.min.js"></script>

<!-- Dashboard JS -->
<script src="/CSR/dashboard/csr_dashboard.js?v=3"></script>

<!-- Chat JS -->
<script src="/CSR/chat/chat.js?v=3"></script>

<!-- History JS -->
<script src="/CSR/history/history.js?v=3"></script>

<script>
/* ===========================================================
   🔐 ENCRYPTED ROUTE NAVIGATION FOR CSR
=========================================================== */

// Sends user to /home.php?v=ENCRYPTED(route)
function navigateEncrypted(routeName) {
    window.location.href = "/home.php?v=" + btoa(routeName + "|" + Date.now());
}

// Buttons – converted to encrypted navigation
function goChat()      { navigateEncrypted("csr_chat"); }
function goClients()   { navigateEncrypted("csr_clients"); }
function goReminders() { navigateEncrypted("csr_reminders"); }
function goSurvey()    { navigateEncrypted("csr_survey"); }

const csrUser     = "<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>";
const csrFullname = "<?= htmlspecialchars($csrFullName, ENT_QUOTES) ?>";
</script>

<style>
.nav-btn.active { background:#008cff; color:white; }
.side-item.active { background:#e0f0ff; font-weight:bold; }
</style>

</head>
<body>

<!-- GLOBAL LOADER -->
<div id="loadingOverlay"><div class="spinner"></div></div>

<!-- TOP NAVIGATION BAR -->
<div class="topnav">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>

    <div class="top-title">
        <img src="../../AHBALOGO.png" class="nav-logo">
        <h2>CSR DASHBOARD — <?= strtoupper($csrUser) ?></h2>
    </div>

    <div class="nav-buttons">
        <button class="nav-btn" onclick="goChat()">💬 CHAT</button>
        <button class="nav-btn" onclick="goClients()">👥 MY CLIENTS</button>
        <button class="nav-btn" onclick="goReminders()">⏱ REMINDERS</button>
        <button class="nav-btn" onclick="goSurvey()">📄 SURVEY</button>
        <a href="../csr_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<!-- MINI ICON SIDEBAR -->
<div class="sidebar-collapsed">
    <button class="icon-btn" onclick="goChat()" title="Chat">💬</button>
    <button class="icon-btn" onclick="goClients()" title="My Clients">👥</button>
    <button class="icon-btn" onclick="goReminders()" title="Reminders">⏱</button>
    <button class="icon-btn" onclick="goSurvey()" title="Survey">📄</button>
    <button class="icon-btn logout" onclick="window.location='../csr_logout.php'" title="Logout">🚪</button>
</div>

<!-- FULL SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="side-title">MENU</div>

    <button class="side-item" onclick="goChat()">💬 Chat Dashboard</button>
    <button class="side-item" onclick="goClients()">👥 My Clients</button>
    <button class="side-item" onclick="goReminders()">⏱ Reminders</button>
    <button class="side-item" onclick="goSurvey()">📄 Survey Responses</button>

    <button class="side-item logout" onclick="window.location='../csr_logout.php'">🚪 Logout</button>
</div>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- MAIN CONTENT -->
<div class="dashboard-container">
<?php

/* ===========================================================
   CONTENT ENGINE (CSR DASHBOARD)
   - home.php already decrypts to csr_chat, csr_clients, etc.
=========================================================== */

// home.php determines what page loads.
// here, we ONLY read which file home.php already included.

if (isset($GLOBALS["CSR_TAB"])) {

    switch ($GLOBALS["CSR_TAB"]) {

        case "CLIENTS":
            if ($ticketID > 0) {
                include "../history/history_view.php";
            } elseif ($clientID > 0) {
                include "../history/history_list.php";
            } else {
                include "../clients/my_clients.php";
            }
            break;

        case "REMINDERS":
            include "../reminders/reminders.php";
            break;

        case "SURVEY":
            include "../survey/survey_responses.php";
            break;

        default:
        case "CHAT":
            include "../chat/chat.php";
            break;
    }
}

?>
</div>

</body>
</html>
