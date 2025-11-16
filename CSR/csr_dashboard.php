<?php
session_start();
include '../db_connect.php';
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csrUser = $_SESSION['csr_user'];
$csr_fullname = $_SESSION['csr_fullname'] ?? $csr_user;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?php echo strtoupper($csrUser); ?></title>
<link rel="stylesheet" href="csr_dashboard.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">×</button>
    <div class="side-title">Menu</div>

    <button class="side-item" onclick="location.href='csr_dashboard.php'">💬 Chat Dashboard</button>
    <button class="side-item" onclick="location.href='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="location.href='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="location.href='survey_responses.php'">📄 Survey Responses</button>
    <button class="side-item" onclick="location.href='update_profile.php'">👤 Edit Profile</button>

    <button class="side-item logout" onclick="location.href='csr_logout.php'">🚪 Logout</button>
</div>

<div id="overlay" class="overlay" onclick="toggleSidebar()"></div>

<!-- TOP NAV -->
<div class="topnav">
    <button class="menu-btn" onclick="toggleSidebar()">☰</button>

    <img src="upload/AHBALOGO.png" class="nav-logo">
    <h2>CSR DASHBOARD — <b><?php echo strtoupper($csrUser); ?></b></h2>

    <div class="nav-buttons">
        <button class="nav-btn active">💬 CHAT DASHBOARD</button>
        <button class="nav-btn" onclick="location.href='my_clients.php'">👥 MY CLIENTS</button>
        <button class="nav-btn" onclick="location.href='reminders.php'">⏱ REMINDERS</button>
        <button class="nav-btn" onclick="location.href='survey_responses.php'">📄 SURVEY RESPONSE</button>
        <button class="nav-btn" onclick="location.href='update_profile.php'">👤 EDIT PROFILE</button>
        <a href="csr_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<!-- MAIN LAYOUT -->
<div class="layout">

    <!-- CLIENT LIST -->
    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input type="text" class="search" id="searchClient" placeholder="Search clients...">

        <div id="clientList"></div>
    </div>

    <!-- CHAT PANEL -->
    <div class="chat-panel">
        <div class="chat-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <img id="chatAvatar" class="chat-avatar">
                <div>
                    <b id="chatName">Select a client</b>
                    <p id="chatStatus">---</p>
                </div>
            </div>
            <button class="info-btn" onclick="openInfo()">ℹ</button>
        </div>

        <div id="chatMessages" class="chat-box">
            <img src="upload/AHBALOGO.png" class="faded-bg">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <div id="previewFiles" class="preview-area"></div>

        <form id="chatForm" enctype="multipart/form-data">
            <button type="button" id="attachBtn" class="upload-icon">📎</button>
            <input type="file" id="fileInput" name="files[]" multiple hidden>
            <input type="text" id="messageInput" placeholder="Type a message..." disabled>
            <button type="button" id="sendBtn" class="send-btn">✈</button>
        </form>
    </div>
</div>

<!-- CLIENT INFO SLIDE PANEL -->
<div id="clientInfoPanel" class="client-info-panel">
    <button onclick="closeInfo()" class="close-info">✕</button>
    <h3>Client Information</h3>
    <p><b>Name:</b> <span id="infoName"></span></p>
    <p><b>District:</b> <span id="infoDistrict"></span></p>
    <p><b>Barangay:</b> <span id="infoBarangay"></span></p>
</div>

<script src="csr_chat.js"></script>
</body>
</html>
