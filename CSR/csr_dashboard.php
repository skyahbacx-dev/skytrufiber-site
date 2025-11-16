<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['csr_user'])) {
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

<link rel="stylesheet" href="csr_dashboard.css?v=2025">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body>

<!-- ===== SIDEBAR (Collapsed by default) ===== -->
<div class="sidebar collapsed" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

    <div class="side-title">Menu</div>
    <button class="side-item" onclick="window.location='csr_dashboard.php'">💬 Chat Dashboard</button>
    <button class="side-item" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📄 Survey Responses</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>

    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>
<div class="sidebar-overlay"></div>

<!-- ===== NAVBAR ===== -->
<div class="topnav">
    <div style="display:flex;align-items:center;gap:10px;">
        <img src="upload/AHBALOGO.png" class="nav-logo">
        <h2>CSR DASHBOARD — <?= strtoupper($csr_fullname) ?></h2>
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

<!-- ===== MAIN LAYOUT ===== -->
<div class="layout">

    <!-- CLIENT LIST -->
    <section class="client-panel">
        <h3>CLIENTS</h3>
        <input class="search" placeholder="Search clients...">
        <div id="clientList" class="client-list"></div>
    </section>

    <!-- CHAT PANEL -->
    <main class="chat-panel">
        <div class="chat-header">
            <div class="chat-header-left">
                <img id="chatAvatar" src="CSR/lion.PNG" class="chat-avatar">
                <div>
                    <div class="chat-name" id="chatName">Select a client</div>
                    <div class="chat-status">
                        <span id="statusDot" class="status-dot offline"></span>
                        <span id="chatStatus">---</span>
                    </div>
                </div>
            </div>
            <button class="info-btn" onclick="toggleClientInfo()">ⓘ</button>
        </div>

        <div class="chat-box" id="chatMessages">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <div id="previewArea" class="preview-area"></div>

        <div class="chat-input">
            <label class="upload-icon">
                📎<input type="file" id="fileInput" multiple style="display:none">
            </label>
            <input type="text" id="messageInput" placeholder="Type message..." disabled>
            <button class="send-btn" id="sendBtn" disabled>✈</button>
        </div>
    </main>

    <!-- SLIDING CLIENT INFO PANEL -->
    <aside id="clientInfoPanel" class="client-info-panel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>
        <h3>Client Information</h3>
        <p><b id="infoName"></b></p>
        <p id="infoEmail"></p>
        <p>District: <span id="infoDistrict"></span></p>
        <p>Barangay: <span id="infoBarangay"></span></p>
        <p>Phone: <span id="infoPhone"></span></p>
    </aside>

</div>

<script src="csr_chat.js?v=2025"></script>
</body>
</html>
