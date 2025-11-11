<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$csr = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $csr['full_name'] ?? $csr_user;
$logoPath = '../SKYTRUFIBER/AHBALOGO.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css">
</head>

<body>

<!-- Sidebar -->
<div id="sidebar">
    <div class="side-header">
        <img src="<?= $logoPath ?>" class="side-logo">
        <span><?= htmlspecialchars($csr_fullname) ?></span>
        <button id="sideClose" onclick="toggleSidebar(false)">✕</button>
    </div>

    <a onclick="switchTab('all')">💬 All Clients</a>
    <a onclick="switchTab('mine')">👤 My Clients</a>
    <a onclick="switchTab('rem')">⏰ Reminders</a>
    <a href="survey_responses.php">📝 Survey Responses</a>
    <a href="update_profile.php">👤 Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<div id="sidebar-overlay" onclick="toggleSidebar(false)"></div>

<!-- Header -->
<header>
    <button id="openSidebar" onclick="toggleSidebar(true)">☰</button>
    <div class="brand">
        <img src="<?= $logoPath ?>" class="brand-logo">
        <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
</header>

<!-- Tabs -->
<div class="tabs">
    <div id="tab-all" class="tab active" onclick="switchTab('all')">💬 All Clients</div>
    <div id="tab-mine" class="tab" onclick="switchTab('mine')">👤 My Clients</div>
    <div id="tab-rem" class="tab" onclick="switchTab('rem')">⏰ Reminders</div>
    <div class="tab" onclick="location.href='survey_responses.php'">📝 Surveys</div>
    <div class="tab" onclick="location.href='update_profile.php'">👤 Profile</div>
</div>

<div id="layout">
    <!-- Client list -->
    <div id="clientList"></div>

    <!-- Chat area -->
    <div id="chatArea">
        <div id="chatHeader">
            <div id="chatAvatar" class="avatar"></div>
            <div class="chatName" id="chatName">Select a client</div>
        </div>

        <div id="messages"></div>

        <div id="inputArea" style="display:none;">
            <input type="text" id="msgBox" placeholder="Type a reply…">
            <button onclick="sendMessage()">Send</button>
        </div>

        <div id="reminderArea"></div>
    </div>
</div>

<script src="csr_ajax.js"></script>

</body>
</html>
