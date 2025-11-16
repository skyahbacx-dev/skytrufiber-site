<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION["csr_user"])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION["csr_user"];
$csr_fullname = $_SESSION["csr_fullname"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= $csr_fullname ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=5">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const csrFullname = <?= json_encode($csr_fullname) ?>;
</script>
<script src="csr_chat.js?v=10"></script>
</head>

<body>

<!-- ===== COLLAPSIBLE SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

    <div class="side-title">MENU</div>
    <button class="side-item active">💬 Chat Dashboard</button>
    <button class="side-item" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📄 Survey Response</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>

    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- ===== TOP NAV BAR ===== -->
<div class="topnav">
    <img src="upload/AHBALOGO.png" class="nav-logo">
    <h2>CSR DASHBOARD — <?= strtoupper($csr_user) ?></h2>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="layout">

    <!-- CLIENT LIST -->
    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input type="text" class="search" placeholder="Search..." onkeyup="searchClient(this.value)">
        <div id="clientList" class="client-list"></div>
    </div>

    <!-- CHAT WINDOW -->
    <div class="chat-panel">

        <div class="chat-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <img id="chatAvatar" class="chat-avatar" src="upload/default-avatar.png">
                <div>
                    <div id="chatName" class="chat-name">Select a client</div>
                    <div id="chatStatus" class="chat-status">
                        <span id="statusDot" class="status-dot offline"></span>Offline
                    </div>
                </div>
            </div>
            <button id="infoBtn" class="info-btn" onclick="toggleClientInfo()">ⓘ</button>
        </div>

        <div id="chatMessages" class="chat-box">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <!-- Preview -->
        <div id="previewArea" class="photo-preview-group"></div>

        <div class="chat-input">
            <label for="fileInput" class="upload-icon">📎</label>
            <input type="file" id="fileInput" multiple style="display:none;">
            <input type="text" id="messageInput" placeholder="Type a message..." disabled>
            <button id="sendBtn" class="send-btn" disabled>✈</button>
        </div>
    </div>

    <!-- CLIENT INFO PANEL -->
    <aside class="client-info-panel" id="clientInfoPanel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>
        <h3>Client Information</h3>
        <p><strong id="infoName"></strong></p>
        <p id="infoEmail"></p>
        <p><b>District:</b> <span id="infoDistrict"></span></p>
        <p><b>Barangay:</b> <span id="infoBrgy"></span></p>
        <p><b>Phone:</b> <span id="infoPhone"></span></p>
        <p><b>Assigned CSR:</b> <span id="infoAssigned"></span></p>
    </aside>

</div>
</body>
</html>
