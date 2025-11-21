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

<!-- TOP NAVBAR -->
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

<!-- SIDEBAR -->
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

<!-- MAIN LAYOUT -->
<div class="layout">

    <!-- CLIENT LIST -->
    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input class="search" id="searchInput" placeholder="Search clients...">
        <div id="clientList" class="client-list"></div>
    </div>

    <!-- CHAT PANEL -->
    <div class="chat-panel">

        <div id="placeholderScreen">Select a client to start chatting</div>

        <div class="chat-header" id="chatHeader" style="display:none;">
            <div class="user-section">
                <img id="chatAvatar" src="upload/default-avatar.png" class="chat-avatar">
                <div>
                    <div id="chatName" class="chat-name">Select a client</div>
                    <div id="chatStatus" class="chat-status">
                        <span id="statusDot" class="status-dot offline"></span> Offline
                    </div>
                </div>
            </div>
            <button class="info-btn" onclick="toggleClientInfo()">ⓘ</button>
        </div>

        <div class="chat-box" id="chatMessages"></div>

        <div id="previewArea" class="preview-area"></div>

        <div class="chat-input">
            <label class="upload-icon"><i class="fa-regular fa-image"></i></label>
            <input type="file" id="fileInput" multiple style="display:none;">
            <input type="text" id="messageInput" placeholder="Type anything.....">
            <button id="sendBtn" class="send-btn"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>

    <!-- CLIENT INFO PANEL -->
    <aside id="clientInfoPanel" class="client-info-panel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>
        <h3>Client Information</h3>
        <p><strong id="infoName"></strong></p>
        <p id="infoEmail"></p>
        <p><b>District:</b> <span id="infoDistrict"></span></p>
        <p><b>Barangay:</b> <span id="infoBrgy"></span></p>
    </aside>
</div>

</body>
</html>
