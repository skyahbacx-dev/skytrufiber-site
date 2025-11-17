<?php
session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csrUser = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?php echo $csrFullName; ?></title>

<link rel="stylesheet" href="csr_dashboard.css?v=23">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const csrFullname = "<?php echo $csrFullName; ?>";
</script>
<script src="csr_chat.js?v=23"></script>

</head>
<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
    <div class="side-title">Menu</div>

    <button class="side-item active">💬 Chat Dashboard</button>
    <button class="side-item" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📑 Survey Responses</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>

    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- ===== TOP NAV BAR ===== -->
<div class="topnav">
    <img src="AHBALOGO.png" class="nav-logo">
    <h2>CSR DASHBOARD — <?php echo strtoupper($csrFullName); ?></h2>

    <div class="nav-buttons">
        <button class="nav-btn active">💬 CHAT DASHBOARD</button>
        <button class="nav-btn" onclick="window.location='my_clients.php'">👥 MY CLIENTS</button>
        <button class="nav-btn" onclick="window.location='reminders.php'">⏱ REMINDERS</button>
        <button class="nav-btn" onclick="window.location='survey_responses.php'">📑 SURVEY RESPONSE</button>
        <button class="nav-btn" onclick="window.location='update_profile.php'">👤 EDIT PROFILE</button>
        <a href="csr_logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<!-- ===== MAIN DASHBOARD ===== -->
<div class="layout">

    <!-- CLIENT LIST -->
    <section class="client-panel">
        <h3>CLIENTS</h3>
        <input class="search" placeholder="Search client...">
        <div id="clientList" class="client-list"></div>
    </section>

    <!-- CHAT PANEL -->
    <main class="chat-panel">
        <div class="chat-header">
            <div class="chat-header-left">
                <img src="CSR/lion.PNG" id="chatAvatar" class="chat-avatar">
                <div>
                    <div id="chatName" class="chat-name">Select a client</div>
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

        <div id="previewArea" class="preview-group"></div>

        <div class="chat-input">
            <label for="fileInput" class="upload-icon">🖼</label>
            <input type="file" id="fileInput" multiple accept="image/*,video/*" style="display:none;">
            <input type="text" id="messageInput" placeholder="Type a message..." disabled>
            <button id="sendBtn" class="send-btn" disabled>✈</button>
        </div>
    </main>

    <!-- CLIENT INFO PANEL -->
    <aside id="clientInfoPanel" class="client-info-panel">
        <button class="close-info" onclick="toggleClientInfo()">✖</button>
        <h3>Clients Information</h3>
        <p><strong id="infoName"></strong></p>
        <p id="infoEmail"></p>
        <p><strong>District:</strong> <span id="infoDistrict"></span></p>
        <p><strong>Barangay:</strong> <span id="infoBrgy"></span></p>
    </aside>

</div>
</body>
</html>
