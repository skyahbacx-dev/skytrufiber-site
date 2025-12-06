<?php
session_start();

if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
$tab = $_GET['tab'] ?? 'chat';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSR Dashboard — <?= htmlspecialchars($csrFullName) ?></title>

<!-- CSS -->
<link rel="stylesheet" href="../csr_dashboard.css">
<link rel="stylesheet" href="../../chat/chat.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Chat System -->
<script src="../../chat/chat.js"></script>

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
        <button class="nav-btn <?= $tab==='chat'?'active':'' ?>" 
            onclick="navigate('chat')">💬 CHAT</button>

        <button class="nav-btn <?= $tab==='clients'?'active':'' ?>" 
            onclick="navigate('clients')">👥 MY CLIENTS</button>

        <button class="nav-btn <?= $tab==='reminders'?'active':'' ?>" 
            onclick="navigate('reminders')">⏱ REMINDERS</button>

        <button class="nav-btn <?= $tab==='survey'?'active':'' ?>" 
            onclick="navigate('survey')">📄 SURVEY</button>

        <a href="../../csr_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="side-title">MENU</div>

    <button class="side-item <?= $tab==='chat'?'active':'' ?>" onclick="navigate('chat')">💬 Chat Dashboard</button>
    <button class="side-item <?= $tab==='clients'?'active':'' ?>" onclick="navigate('clients')">👥 My Clients</button>
    <button class="side-item <?= $tab==='reminders'?'active':'' ?>" onclick="navigate('reminders')">⏱ Reminders</button>
    <button class="side-item <?= $tab==='survey'?'active':'' ?>" onclick="navigate('survey')">📄 Survey Responses</button>
    
    <button class="side-item logout" onclick="window.location='../../csr_logout.php'">🚪 Logout</button>
</div>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- MAIN CONTENT -->
<div class="dashboard-container">
<?php
    switch ($tab) {
        case 'clients':   include "../my_clients.php"; break;
        case 'reminders': include "../reminders.php"; break;
        case 'survey':    include "../survey/survey_responses.php"; break;

        default:
        case 'chat':      include "../../chat/chat.php"; break;
    }
?>
</div>

<script>
function navigate(tab) {
    showLoader();
    window.location = "csr_dashboard.php?tab=" + tab;
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.querySelector('.sidebar-overlay').classList.toggle('active');
}

/* LOADER */
function showLoader(){ document.getElementById("loadingOverlay").style.display="flex"; }
function hideLoader(){ document.getElementById("loadingOverlay").style.display="none"; }
window.onload = hideLoader;
</script>

</body>
</html>
