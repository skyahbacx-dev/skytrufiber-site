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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const csrFullname = "<?= $csrFullName ?>";
</script>
<script src="csr_chat.js"></script>
</head>

<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">❐</button>
    
    <div class="side-title">MENU</div>
    <button class="side-item" onclick="window.location='csr_dashboard.php'">💬 Chat Dashboard</button>
    <button class="side-item" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📄 Survey Responses</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>

    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- ===== TOP NAV ===== -->
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

<!-- ===== LAYOUT ===== -->
<div class="layout">

    <!-- CLIENT LIST -->
    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input class="search" placeholder="Search clients...">
        <div id="clientList" class="client-list"></div>
    </div>

    <!-- CHAT PANEL -->
    <div class="chat-panel">
        <div class="chat-header">
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

        <!-- PREVIEW -->
        <div id="previewArea" class="preview-area"></div>

        <!-- INPUT -->
        <div class="chat-input">
            <label for="fileInput" class="upload-icon">📎</label>
            <input type="file" id="fileInput" multiple style="display:none;">
            <input type="text" id="messageInput" placeholder="Write a message..." disabled>
            <button id="sendBtn" class="send-btn" disabled>✈</button>
        </div>
    </div>

    <!-- SLIDING CLIENT INFO -->
    <aside id="clientInfoPanel" class="client-info-panel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>
        <h3>Client Information</h3>
        <p><strong id="infoName"></strong></p>
        <p id="infoEmail"></p>
        <p><b>District:</b></p><p id="infoDistrict"></p>
        <p><b>Barangay:</b></p><p id="infoBrgy"></p>
    </aside>

</div>

</body>
</html>
