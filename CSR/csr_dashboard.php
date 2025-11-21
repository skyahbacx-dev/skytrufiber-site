<?php
session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csrUser     = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csrFullName) ?></title>

<link rel="stylesheet" href="csr_dashboard.css">
<link rel="stylesheet" href="chat.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const csrUser     = "<?= htmlspecialchars($csrUser, ENT_QUOTES) ?>";
const csrFullname = "<?= htmlspecialchars($csrFullName, ENT_QUOTES) ?>";
</script>

<script src="csr_chat.js"></script>

</head>
<body>

<!-- TOP NAV -->
<div class="topnav">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>

    <div class="top-title">
        <img src="../AHBALOGO.png" class="nav-logo">
        <h2>CSR DASHBOARD — <?= strtoupper($csrUser) ?></h2>
    </div>

    <div class="nav-buttons">
        <button class="nav-btn active">💬 CHAT DASHBOARD</button>
        <button class="nav-btn" onclick="window.location='my_clients.php'">👥 MY CLIENTS</button>
        <button class="nav-btn" onclick="window.location='reminders.php'">⏱ REMINDERS</button>
        <button class="nav-btn" onclick="window.location='survey_responses.php'">📄 SURVEY</button>
        <button class="nav-btn" onclick="window.location='update_profile.php'">👤 PROFILE</button>
        <a href="csr_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<!-- SIDEBAR NAVIGATION -->
<div class="sidebar" id="sidebar">
    <div class="side-title">MENU</div>
    <button class="side-item" onclick="window.location='csr_dashboard.php'">💬 Chat Dashboard</button>
    <button class="side-item" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📄 Survey Responses</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>
    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- MAIN CONTENT FRAME -->
<div class="layout">
    <?php include "chat.php"; ?>
</div>
<!-- ASSIGN CONFIRM POPUP -->
<div id="assignPopup" class="popupBox">
    <div class="popupContent">
        <h3>Assign This Client To You?</h3>
        <div class="popup-buttons">
            <button onclick="confirmAssign()">YES</button>
            <button onclick="closeAssignPopup()">NO</button>
        </div>
    </div>
</div>

<!-- UNASSIGN CONFIRM POPUP -->
<div id="unassignPopup" class="popupBox">
    <div class="popupContent">
        <h3>Remove This Client?</h3>
        <div class="popup-buttons">
            <button onclick="confirmUnassign()">YES</button>
            <button onclick="closeUnassignPopup()">NO</button>
        </div>
    </div>
</div>

</body>
</html>
