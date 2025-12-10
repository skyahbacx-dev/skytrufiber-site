<?php
session_start();

if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
$tab = $_GET['tab'] ?? 'chat';

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
<link rel="stylesheet" href="../history/history.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Dashboard JS -->
<script src="csr_dashboard.js?v=2"></script>

<!-- Chat System JS -->
<script src="../chat/chat.js?v=2"></script>

<script>
const csrUser     = "<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>";
const csrFullname = "<?= htmlspecialchars($csrFullName, ENT_QUOTES) ?>";
</script>
</head>

<body>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay">
    <div class="spinner"></div>
</div>

<!-- TOP NAV -->
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
<!-- COLLAPSED ICON SIDEBAR (ALWAYS VISIBLE) -->
<div class="sidebar-collapsed">
    <button class="icon-btn" onclick="navigate('chat')" title="Chat">💬</button>
    <button class="icon-btn" onclick="navigate('clients')" title="My Clients">👥</button>
    <button class="icon-btn" onclick="navigate('reminders')" title="Reminders">⏱</button>
    <button class="icon-btn" onclick="navigate('survey')" title="Survey">📄</button>
    <button class="icon-btn logout" onclick="window.location='../csr_logout.php'" title="Logout">🚪</button>
</div>

<!-- FULL SIDEBAR (WHEN EXPANDED) -->
<div class="sidebar" id="sidebar">
    <div class="side-title">MENU</div>

    <button class="side-item <?= $tab==='chat'?'active':'' ?>" onclick="navigate('chat')">💬 Chat Dashboard</button>
    <button class="side-item <?= $tab==='clients'?'active':'' ?>" onclick="navigate('clients')">👥 My Clients</button>
    <button class="side-item <?= $tab==='reminders'?'active':'' ?>" onclick="navigate('reminders')">⏱ Reminders</button>
    <button class="side-item <?= $tab==='survey'?'active':'' ?>" onclick="navigate('survey')">📄 Survey Responses</button>

    <button class="side-item logout" onclick="window.location='../csr_logout.php'">🚪 Logout</button>
</div>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<!-- MAIN CONTENT -->
<div class="dashboard-container">

<?php
switch ($tab) {

    case 'clients':

        // If user clicked history ITEM inside My Clients page
        if ($ticketID > 0) {
            include "../history/history_view.php";
        }
        // Show list of tickets for a client
        else if ($clientID > 0) {
            include "../history/history_list.php";
        }
        // Regular My Clients page
        else {
            include "../clients/my_clients.php";
        }
        break;

    case 'reminders':
        include "../reminders/reminders.php";
        break;

    case 'survey':
        include "../survey/survey_responses.php";
        break;

    default:
    case 'chat':
        include "../chat/chat.php";
        break;
}
?>
</div>

<script src="csr_dashboard.js"></script>
</body>
</html>
