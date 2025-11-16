<?php
session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csrUser = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?php echo $csrFullName; ?></title>
<link rel="stylesheet" href="csr_dashboard.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="csr_chat.js"></script>
</head>

<body>

<!-- ====== TOP NAV BAR ====== -->
<div class="topnav">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

    <div class="nav-title">
        <img src="../upload/AHBALOGO.png" style="height:55px;margin-right:10px;">
        <h2>CSR DASHBOARD — <?php echo strtoupper($_SESSION['csr_user']); ?></h2>
    </div>

    <div class="nav-buttons">
        <button class="nav-btn active">💬 CHAT DASHBOARD</button>
        <button class="nav-btn" onclick="window.location='my_clients.php'">👥 MY CLIENTS</button>
        <button class="nav-btn" onclick="window.location='reminders.php'">⏱ REMINDERS</button>
        <button class="nav-btn" onclick="window.location='survey_responses.php'">📄 SURVEY RESPONSE</button>
        <button class="nav-btn" onclick="window.location='update_profile.php'">👤 EDIT PROFILE</button>
        <a href="csr_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<!-- ====== SLIDING SIDEBAR ====== -->
<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">×</button>
    <div class="side-title">Menu</div>
    <button class="side-item" onclick="window.location='csr_dashboard.php'">💬 Chat Dashboard</button>
    <button class="side-item" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📄 Survey Responses</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>
    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- ====== MAIN LAYOUT (RESTORED) ====== -->
<div class="layout">

    <!-- LEFT: CLIENT LIST -->
    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input type="text" class="search" placeholder="Search clients...">
        <div id="clientList"></div>
    </div>

    <!-- RIGHT: CHAT PANEL -->
    <div class="chat-panel">
        <div class="chat-header">
            <img id="chatAvatar" class="chat-avatar">
            <div>
                <div class="chat-name" id="chatName">Select a client</div>
                <div class="chat-status"><span id="statusDot" class="status-dot offline"></span>---</div>
            </div>
            <button class="info-btn" onclick="toggleClientInfo()">ℹ</button>
        </div>

        <div class="chat-box" id="chatMessages">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <div class="preview-area" id="previewArea"></div>

        <div class="chat-input">
            <label for="fileInput" class="file-btn">📁</label>
            <input type="file" id="fileInput" accept="image/*,video/*" multiple style="display:none;">
            <input type="text" id="messageInput" placeholder="Type a message..." disabled>
            <button class="send-btn" id="sendBtn">✈</button>
        </div>
    </div>
</div>

<!-- ===== CLIENT INFO SLIDING PANEL ===== -->
<div id="clientInfoPanel">
    <button class="close-info" onclick="toggleClientInfo()">×</button>
    <h3>Client Information</h3>
    <p><b>Name:</b> <span id="infoName"></span></p>
    <p><b>Email:</b> <span id="infoEmail"></span></p>
    <p><b>District:</b> <span id="infoDistrict"></span></p>
    <p><b>Barangay:</b> <span id="infoBarangay"></span></p>
    <p><b>Phone:</b> <span id="infoPhone"></span></p>
</div>

<script src="csr_chat.js"></script>

</body>
</html>
