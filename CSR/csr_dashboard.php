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
    <button class="close-sidebar" onclick="toggleSidebar(false)">✖</button>

    <h2><?= htmlspecialchars($csr_fullname) ?></h2>

    <a onclick="switchTab('all')">💬 All Clients</a>
    <a onclick="switchTab('mine')">👤 My Clients</a>
    <a onclick="switchTab('rem')">⏰ Reminders</a>
    <a href="survey_responses.php">📝 Surveys</a>
    <a href="update_profile.php">⚙️ Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<div id="sidebar-overlay" onclick="toggleSidebar(false)"></div>

<!-- HEADER -->
<header>
    <button id="openSidebar" onclick="toggleSidebar(true)">☰</button>
    <div class="centered-title">CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></div>
</header>

<!-- TABS -->
<div class="tabs">
    <button id="tab-all" class="active" onclick="switchTab('all')">All Clients</button>
    <button id="tab-mine" onclick="switchTab('mine')">My Clients</button>
    <button id="tab-rem" onclick="switchTab('rem')">Reminders</button>
    <button onclick="location.href='survey_responses.php'">Surveys</button>
    <button onclick="location.href='update_profile.php'">Profile</button>
</div>

<!-- MAIN -->
<div id="main">
    <div id="client-col"></div>

    <div id="chat-col">
        <div id="chat-head">
            <div id="chatAvatar" class="avatar"></div>
            <div id="chat-title">Select a client</div>
        </div>

        <div id="messages"></div>

        <div id="input" style="display:none;">
            <input id="msg" placeholder="Type a reply…">
            <button onclick="sendMsg()">Send</button>
        </div>
    </div>
</div>

<script src="csr_dashboard.js"></script>

</body>
</html>
