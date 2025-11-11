<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// GET CSR DETAILS
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u'=>$csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css">
</head>

<body>

<!-- SIDEBAR -->
<div id="sidebar">
    <button id="closeSidebar" onclick="toggleSidebar()">✖</button>
    <h2>CSR Menu</h2>
    <a onclick="switchTab('all')">💬 All Clients</a>
    <a onclick="switchTab('mine')">👤 My Clients</a>
    <a onclick="switchTab('rem')">⏰ Reminders</a>
    <a href="survey_responses.php">📝 Survey Responses</a>
    <a href="update_profile.php">👤 Edit Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- HEADER -->
<header>
    <button id="hamb" onclick="toggleSidebar()">☰</button>
    <div class="brand">
        <img src="<?= $logoPath ?>" alt="Logo">
        <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
</header>

<!-- TABS -->
<div id="tabs">
    <div id="tab-all" class="tab active" onclick="switchTab('all')">💬 All Clients</div>
    <div id="tab-mine" class="tab" onclick="switchTab('mine')">👤 My Clients</div>
    <div id="tab-rem" class="tab" onclick="switchTab('rem')">⏰ Reminders</div>
     <a href="survey_responses.php">📝 Survey Responses</a>
     <a href="update_profile.php">👤 Edit Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<!-- MAIN GRID -->
<div id="main">

    <!-- LEFT: CLIENTS -->
    <div id="client-col"></div>

    <!-- RIGHT: CHAT -->
    <div id="chat-col">

        <button id="collapseBtn" onclick="collapseChat()">●</button>

        <div id="chat-head">
            <div class="chat-title">
                <div id="chatAvatar" class="avatar"></div>
                <div>
                    <div id="chat-name">Select a client</div>
                    <div id="status" class="status">Offline</div>
                </div>
            </div>
            <div class="info-dot">i</div>
        </div>

        <div id="messages"></div>

        <div id="typingIndicator" style="display:none;">Typing...</div>

        <div id="input" style="display:none;">
            <input id="msg" placeholder="Type a reply…" onkeyup="typing()">
            <button onclick="sendMsg()">Send</button>
        </div>

        <!-- REMINDERS -->
        <div id="reminders">
            <input id="rem-q" placeholder="Search…" onkeyup="loadReminders()">
            <div id="rem-list"></div>
        </div>

    </div>

</div>

<script src="csr_dashboard.js"></script>

</body>
</html>
