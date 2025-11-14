<?php
session_start();
include '../db_connect.php';

$csr_user = $_SESSION['username'] ?? '';
$csr_fullname = $_SESSION['full_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard - SkyTruFiber</title>
<link rel="stylesheet" href="csr_dashboard.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

<header class="top-header">
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>

    <div class="logo-area">
        <img src="../SKYTRUFIBER.png" class="logo">
        <h1>CSR DASHBOARD - <?php echo strtoupper($csr_user); ?></h1>
    </div>

    <a href="csr_logout.php" class="logout-btn">🔒 LOGOUT</a>
</header>

<div class="wrap">

    <!-- SIDEBAR NAV -->
    <aside id="sidebar" class="sidebar hidden">
        <button class="close-sidebar" onclick="toggleSidebar()">✖</button>
        <ul class="menu-links">
            <li><a href="csr_dashboard.php">💬 Chat Dashboard</a></li>
            <li><a href="csr_clients.php">👥 My Clients</a></li>
            <li><a href="reminders.php">⏰ Reminders</a></li>
            <li><a href="survey_responses.php">📝 Survey Responses</a></li>
            <li><a href="update_profile.php">👤 Edit Profile</a></li>
        </ul>
    </aside>

    <!-- CLIENT LIST COLUMN -->
    <section class="client-panel">
        <div class="client-title">
            <img src="icons/clients.svg" width="40">
            <h2>CLIENTS</h2>
        </div>

        <div class="client-search">
            <input type="text" id="searchClient" placeholder="Search client...">
            <span>🔍</span>
        </div>

        <div id="clientList" class="client-container"></div>
    </section>

    <!-- CHAT PANEL -->
    <main class="chat-area">
        <div class="chat-header">
            <h2 id="clientName">SELECT CLIENT</h2>
            <button class="client-info-btn" onclick="toggleClientInfo()">ℹ️</button>
        </div>

        <div id="clientInfo" class="client-info hidden"></div>
        <div id="messages" class="messages"></div>

        <div id="chatInput" class="chat-input hidden">
            <input id="msg" type="text" placeholder="Type a message...">
            <button onclick="sendMessage()">➤</button>
        </div>
    </main>
</div>

<script src="csr_dashboard.js"></script>
</body>
</html>
