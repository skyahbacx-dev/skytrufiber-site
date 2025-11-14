<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

$stmt = $conn->prepare("SELECT full_name, profile_pic FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;
$csr_avatar = $row['profile_pic'] ?? 'CSR/default_avatar.png';

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    // Load clients
    if ($_GET['ajax'] === 'load_clients') {
        $stmt = $conn->query("
            SELECT id, name, assigned_csr, last_active,
            (CASE WHEN last_active > NOW() - INTERVAL '60 seconds' THEN 1 ELSE 0 END) AS online
            FROM clients ORDER BY name ASC
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Load single client info
    if ($_GET['ajax'] === 'client_info') {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = :i");
        $stmt->execute([':i' => $id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit;
    }

    // Load chat
    if ($_GET['ajax'] === 'chat') {
        $cid = (int)$_GET['client_id'];
        $stmt = $conn->prepare("
            SELECT * FROM chat WHERE client_id = :cid ORDER BY created_at ASC
        ");
        $stmt->execute([':cid' => $cid]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Send message
    if ($_GET['ajax'] === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $cid = (int)$_POST['client_id'];
        $msg = trim($_POST['msg']);

        if ($msg !== '') {
            $stmt = $conn->prepare("
                INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
                VALUES (:cid, 'csr', :m, :c, NOW())
            ");
            $stmt->execute([':cid' => $cid, ':m' => $msg, ':c' => $csr_fullname]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=10">
</head>

<body>

<header class="topbar">
    <div class="left">
        <img src="AHBALOGO.png" class="logo">
        <h1>CSR DASHBOARD - <?= htmlspecialchars($csr_fullname) ?></h1>
    </div>
    <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
    <a class="logout" href="csr_logout.php">Logout</a>
</header>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar collapsed" id="sidebar">
        <button class="close-btn" onclick="toggleSidebar()">✖</button>

        <button class="nav-btn">💬 Chat Dashboard</button>
        <button class="nav-btn">👤 My Clients</button>
        <button class="nav-btn">📝 Survey Responses</button>
        <button class="nav-btn">⚙ Update Profile</button>

        <h3>CLIENTS</h3>
        <input type="text" id="search" placeholder="Search clients">

        <div id="clientList"></div>
    </aside>

    <!-- MAIN CHAT -->
    <main class="chat-area">
        <div class="chat-header">
            <span id="selected-client">Select a client</span>
        </div>

        <div class="messages" id="messages">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <div class="chat-input">
            <input type="text" id="msg" placeholder="Type a message..." onkeyup="if(event.key==='Enter') sendMessage()">
            <button onclick="sendMessage()">➤</button>
        </div>
    </main>

    <!-- SLIDING INFO PANEL -->
    <aside id="clientInfoPanel" class="info-panel">
        <button class="close-info" onclick="closeInfo()">✖</button>
        <h2>Client Information</h2>
        <div id="client-info-content"></div>
    </aside>

</div>

<script src="client_panel.js?v=3"></script>

</body>
</html>
