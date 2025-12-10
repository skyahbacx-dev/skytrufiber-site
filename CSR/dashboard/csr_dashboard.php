<?php
session_start();

if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
$tab         = $_GET['tab'] ?? 'chat';

/* OPTIONAL history parameters */
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
<link rel="stylesheet" href="csr_dashboard.css">
<link rel="stylesheet" href="../chat/chat.css">
<link rel="stylesheet" href="../history/history.css"> <!-- FIXED -->

<!-- LOCAL FONT AWESOME (no CDN issues) -->
<link rel="stylesheet" href="../../vendor/fontawesome/css/all.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Dashboard JS -->
<script src="csr_dashboard.js?v=3"></script>

<!-- Chat System JS -->
<script src="../chat/chat.js?v=3"></script>

<!-- History JS (loaded early but harmless) -->
<script src="../history/history.js?v=3"></script>

<script>
const csrUser     = "<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>";
const csrFullname = "<?= htmlspecialchars($csrFullName, ENT_QUOTES) ?>";
</script>
</head>


<body>

<!-- GLOBAL LOADER -->
<div id="loadingOverlay">
    <div class="spinner"></div>
</div>

<!-- TOP NAVBAR -->
<div class="topnav">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>

    <div class="top-title">
        <img src="../../AHBALOGO.png" class="nav-logo">
        <h2>CSR DASHBOARD — <?= strtoupper($csrUser) ?></h2>
    </div>

    <div class="nav-buttons">
        <button class="nav-btn <?= $tab==='chat'?'active':'' ?>" onclick="navigate('chat')">💬 CHAT</button>
        <button class="nav-btn <?= $tab==='clients'?'active':'' ?>" onclick="navigate('clients')">👥 MY CLIENTS</button>
        <button class="nav-btn <?= $tab==='reminders'?'active':'' ?>" onclick="navigate('reminders')">⏱ REMINDERS</button>
        <button class="nav-btn <?= $tab==='survey'?'active':'' ?>" onclick="navigate('survey')">📄 SURVEY</button>
        <a href="../csr_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<!-- PERMANENT ICON SIDEBAR (collapsed mode) -->
<div class="sidebar-collapsed">
    <button class="icon-btn" onclick="navigate('chat')" title="Chat">💬</button>
    <button class="icon-btn" onclick="navigate('clients')" title="My Clients">👥</button>
    <button class="icon-btn" onclick="navigate('reminders')" title="Reminders">⏱</button>
    <button class="icon-btn" onclick="navigate('survey')" title="Survey">📄</button>
    <button class="icon-btn logout" onclick="window.location='../csr_logout.php'" title="Logout">🚪</button>
</div>

<!-- EXPANDING SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="side-title">MENU</div>

    <button class="side-item <?= $tab==='chat'?'active':'' ?>" onclick="navigate('chat')">💬 Chat Dashboard</button>
    <button class="side-item <?= $tab==='clients'?'active':'' ?>" onclick="navigate('clients')">👥 My Clients</button>
    <button class="side-item <?= $tab==='reminders'?'active':'' ?>" onclick="navigate('reminders')">⏱ Reminders</button>
    <button class="side-item <?= $tab==='survey'?'active':'' ?>" onclick="navigate('survey')">📄 Survey Responses</button>

    <button class="side-item logout" onclick="window.location='../csr_logout.php'">🚪 Logout</button>
</div>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- MAIN PAGE CONTENT -->
<div class="dashboard-container">

<?php
switch ($tab) {

    // ==========================
    //       MY CLIENTS TAB
    // ==========================
    case 'clients':

        // If user clicked a specific TICKET
        if ($ticketID > 0) {
            include "../history/history_view.php";
        }
        // If user clicked HISTORY for a CLIENT
        else if ($clientID > 0) {
            include "../history/history_list.php";
        }
        // Default My Clients Page
        else {
            include "../clients/my_clients.php";
        }
        break;

    // ==========================
    //       REMINDERS
    // ==========================
    case 'reminders':
        include "../reminders/reminders.php";
        break;

    // ==========================
    //       SURVEY
    // ==========================
    case 'survey':
        include "../survey/survey_responses.php";
        break;

    // ==========================
    //       CHAT DASHBOARD
    // ==========================
    default:
    case 'chat':
        include "../chat/chat.php";
        break;
}
?>
</div>

</body>
</html>
