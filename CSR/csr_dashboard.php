<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

// CORRECT session variable usage
$csr_user     = $_SESSION["csr_user"];
$csr_fullname = $_SESSION["csr_fullname"] ?? $csr_user;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= $csr_fullname ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=99">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

<!-- ===== OVERLAY FOR SIDEBAR ===== -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
    <div class="side-title">Menu</div>

    <button class="side-item" onclick="window.location='csr_dashboard.php'">💬 Chat Dashboard</button>
    <button class="side-item" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📄 Survey Responses</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>

    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>

<!-- ===== TOP NAV ===== -->
<div class="topnav">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

    <div style="display:flex;align-items:center;gap:10px;">
        <img src="upload/AHBALOGO.png" class="nav-logo">
        <h2>CSR DASHBOARD — <?= strtoupper($csr_user) ?></h2>
    </div>

    <div class="nav-buttons">
        <button class="nav-btn active">💬 CHAT DASHBOARD</button>
        <button class="nav-btn" onclick="window.location='my_clients.php'">👥 MY CLIENTS</button>
        <button class="nav-btn" onclick="window.location='reminders.php'">⏱ REMINDERS</button>
        <button class="nav-btn" onclick="window.location='survey_responses.php'">📄 SURVEY RESPONSES</button>
        <button class="nav-btn" onclick="window.location='update_profile.php'">👤 EDIT PROFILE</button>
        <a href="csr_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<!-- ===== MAIN LAYOUT ===== -->
<div class="layout">

    <!-- CLIENTS LIST -->
    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input type="text" class="search" placeholder="Search clients...">
        <div id="clientList" class="client-list"></div>
    </div>

    <!-- CHAT PANEL -->
    <div class="chat-panel">
        <div class="chat-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <img id="chatAvatar" class="chat-avatar" src="CSR/default-avatar.png">
                <div>
                    <div id="chatName" class="chat-name">Select a client</div>
                    <div class="chat-status">
                        <span id="statusDot" class="status-dot offline"></span>
                        <span id="chatStatus">---</span>
                    </div>
                </div>
            </div>
            <button id="infoBtn" class="info-btn" onclick="toggleClientInfo()">ⓘ</button>
        </div>

        <div id="chatMessages" class="chat-box" style="background:url('upload/AHBALOGO.png') center no-repeat;background-size:420px;">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <div id="previewArea" class="photo-preview-group"></div>

        <div class="chat-input">
            <label for="fileInput" class="upload-icon">🖼</label>
            <input type="file" id="fileInput" multiple style="display:none">
            <input type="text" id="messageInput" placeholder="Type a message...">
            <button class="send-btn" id="sendBtn">✈</button>
        </div>
    </div>

    <!-- SLIDING INFO PANEL -->
    <aside id="clientInfoPanel" class="client-info-panel">
        <button onclick="toggleClientInfo()" class="close-info">✖</button>
        <h3>Client Information</h3>
        <p><strong>Name:</strong> <span id="infoName"></span></p>
        <p><strong>Email:</strong> <span id="infoEmail"></span></p>
        <p><strong>District:</strong> <span id="infoDistrict"></span></p>
        <p><strong>Barangay:</strong> <span id="infoBrgy"></span></p>
    </aside>
</div>

<script>
const csrFullname = <?= json_encode($csr_fullname) ?>;
</script>

<script src="csr_chat.js?v=77"></script>

</body>
</html>
