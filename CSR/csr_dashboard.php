<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];
$csr_fullname = $_SESSION['csr_fullname'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=<?= time() ?>">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

<!-- ===== COLLAPSIBLE SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

    <div class="side-title">Menu</div>
    <button class="side-item" onclick="window.location='csr_dashboard.php'">💬 Chat Dashboard</button>
    <button class="side-item" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📄 Survey Responses</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>

    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>

<div id="overlay" class="overlay" onclick="toggleSidebar()"></div>

<!-- ===== TOP NAV ===== -->
<header class="topnav">
    <div class="left">
        <button class="top-toggle" onclick="toggleSidebar()">☰</button>
        <img src="upload/AHBALOGO.png" class="nav-logo">
        <h2 class="nav-title">CSR DASHBOARD — <?= strtoupper($csr_user) ?></h2>
    </div>

    <div class="nav-buttons">
        <button class="nav-btn active">💬 CHAT DASHBOARD</button>
        <button class="nav-btn" onclick="window.location='my_clients.php'">👥 MY CLIENTS</button>
        <button class="nav-btn" onclick="window.location='reminders.php'">⏱ REMINDERS</button>
        <button class="nav-btn" onclick="window.location='survey_responses.php'">📄 SURVEY RESPONSE</button>
        <button class="nav-btn" onclick="window.location='update_profile.php'">👤 EDIT PROFILE</button>
        <a href="csr_logout.php" class="logout-btn">Logout</a>
    </div>
</header>

<div class="layout">

    <!-- ===== CLIENT LIST PANEL ===== -->
    <section class="client-panel">
        <h3>CLIENTS</h3>
        <input class="search" placeholder="Search clients...">
        <div id="clientList" class="client-list"></div>
    </section>

    <!-- ===== CHAT PANEL ===== -->
    <main class="chat-panel">
        <div class="chat-header">
            <div class="chat-header-left">
                <img id="chatAvatar" src="CSR/lion.PNG" class="chat-avatar">
                <div>
                    <div id="chatName" class="chat-name">Select a client</div>
                    <div class="chat-status">
                        <span id="statusDot" class="status-dot offline"></span>
                        <span id="chatStatus">---</span>
                    </div>
                </div>
            </div>
            <button id="infoBtn" class="info-btn">ⓘ</button>
        </div>

        <div id="chatBox" class="chat-box">
            <div class="placeholder">Select a client to start chatting.</div>
        </div>

        <div id="uploadPreview" class="photo-preview-group" style="display:none;"></div>

        <div id="chatInput" class="chat-input disabled">
            <label for="fileUpload" class="upload-icon">🖼</label>
            <input type="file" id="fileUpload" multiple style="display:none">
            <input type="text" id="msg" placeholder="Type a message..." disabled>
            <button id="sendBtn" class="send-btn" disabled>✈</button>
        </div>
    </main>

    <!-- ===== CLIENT INFO SLIDE PANEL ===== -->
    <aside id="clientInfoPanel" class="client-info-panel">
        <button class="close-info">✖</button>
        <h3>Client Information</h3>
        <p><strong id="infoName"></strong></p>
        <p id="infoEmail"></p>
        <p>District:</p><p id="infoDistrict"></p>
        <p>Barangay:</p><p id="infoBrgy"></p>
    </aside>

</div>

<script src="csr_chat.js?v=<?= time() ?>"></script>
</body>
</html>
