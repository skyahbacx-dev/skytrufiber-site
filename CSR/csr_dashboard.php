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

<body class="sidebar-collapsed">

<!-- OVERLAY SIDEBAR -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
    <button class="sidebar-close" onclick="toggleSidebar()">×</button>

    <div class="side-title">MENU</div>

    <button class="side-item" onclick="window.location='csr_dashboard.php'">💬 Chat Dashboard</button>
    <button class="side-item" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📄 Survey Responses</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>
    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>

<!-- TOP NAVBAR -->
<div class="topnav">
    <button class="menu-btn" onclick="toggleSidebar()">☰</button>

    <div class="nav-center">
        <img src="upload/AHBALOGO.png" class="nav-logo">
        <h2>CSR DASHBOARD — <?php echo strtoupper($csrUser); ?></h2>
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

<!-- MAIN LAYOUT -->
<div class="layout">

    <!-- CLIENT LIST -->
    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input type="text" class="search" placeholder="Search clients...">

        <div class="client-list" id="clientList"></div>
    </div>

    <!-- CHAT PANEL -->
    <div class="chat-panel">

        <div class="chat-header">
            <div class="chat-info">
                <img id="chatAvatar" class="chat-avatar">
                <div>
                    <div class="chat-name" id="chatName">Select a client</div>
                    <div class="chat-status"><span class="status-dot offline"></span>---</div>
                </div>
            </div>

            <button class="info-btn" onclick="toggleClientInfo()">ℹ</button>
        </div>

        <div class="chat-box" id="chatMessages">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <!-- INPUT AREA + PREVIEW -->
        <div id="previewArea"></div>

        <div class="chat-input">
            <label class="file-btn">
                📁
                <input type="file" id="fileInput" multiple accept="image/*,video/*">
            </label>
            <input type="text" id="messageInput" placeholder="Type a message..." disabled>
            <button class="send-btn" id="sendBtn">✈</button>
        </div>
    </div>

    <!-- CLIENT INFO SLIDING PANEL -->
    <div class="client-info-panel" id="clientInfoPanel">
        <button class="close-info" onclick="toggleClientInfo()">×</button>
        <h3>Client Information</h3>
        <div id="clientInfoData"></div>
    </div>

</div>
<script src="csr_chat.js?v=<?= time() ?>"></script>
</body>
</html>
