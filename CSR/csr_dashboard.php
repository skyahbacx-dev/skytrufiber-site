<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION["csr_user"];
$csr_fullname = $_SESSION["csr_fullname"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

    <div class="side-title">MENU</div>
    <button class="side-item" onclick="window.location='csr_dashboard.php'">💬 Chat Dashboard</button>
    <button class="side-item" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📄 Survey Responses</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>

    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>

<!-- ===== TOP NAVIGATION ===== -->
<header class="topnav">
  <img src="upload/AHBALOGO.png" class="nav-logo">
  <h2>CSR DASHBOARD — <?= strtoupper($csr_fullname) ?></h2>

  <nav class="nav-buttons">
      <button class="nav-btn active">💬 CHAT DASHBOARD</button>
      <button class="nav-btn" onclick="window.location='my_clients.php'">👥 MY CLIENTS</button>
      <button class="nav-btn" onclick="window.location='reminders.php'">⏱ REMINDERS</button>
      <button class="nav-btn" onclick="window.location='survey_responses.php'">📑 SURVEY RESPONSE</button>
      <button class="nav-btn" onclick="window.location='update_profile.php'">👤 EDIT PROFILE</button>
  </nav>

  <a href="csr_logout.php" class="logout-btn">Logout</a>
</header>

<!-- ===== MAIN LAYOUT ===== -->
<div class="layout">

    <!-- CLIENT LIST -->
    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input type="text" class="search" placeholder="Search clients..." id="searchClient">

        <div class="client-list" id="clientList"></div>
    </div>

    <!-- CHAT PANEL -->
    <div class="chat-panel">
        <div class="chat-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <img id="chatAvatar" class="chat-avatar">
                <div>
                    <div class="chat-name" id="chatName">Select a client</div>
                    <div class="chat-status">
                        <span class="status-dot offline" id="statusDot"></span>
                        <span id="chatStatus">---</span>
                    </div>
                </div>
            </div>

            <button class="info-btn" onclick="toggleInfo()">ⓘ</button>
        </div>

        <div class="chat-box" id="chatMessages"
            style="background:url('upload/AHBALOGO.png') center/35% no-repeat;">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <!-- Preview Box -->
        <div id="previewBox" class="photo-preview-group"></div>

        <div class="chat-input" id="inputBar">
            <input type="file" id="fileUpload" multiple style="display:none">
            <label for="fileUpload" class="upload-icon">📎</label>

            <input type="text" id="messageInput" placeholder="Type your message..." disabled>
            <button class="send-btn" id="sendBtn">✈</button>
        </div>
    </div>
</div>

<!-- CLIENT INFO SLIDE PANEL -->
<aside id="clientInfoPanel" class="client-info-panel">
  <button class="close-info" onclick="toggleInfo()">✖</button>
  <h3>Client Information</h3>
  <p><strong id="infoName"></strong></p>
  <p>Email: <span id="infoEmail"></span></p>
  <p>District: <span id="infoDistrict"></span></p>
  <p>Barangay: <span id="infoBarangay"></span></p>
</aside>

<script>
let selectedClient=0;

// Sidebar toggle
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("collapsed");
    document.querySelector(".topnav").classList.toggle("collapseShift");
    document.querySelector(".layout").classList.toggle("collapseShift");
}

// Toggle info slide panel
function toggleInfo(){
    document.getElementById("clientInfoPanel").classList.toggle("active");
}

/* CLIENT LIST */
function loadClients(){
    $.get("client_list.php", function(data){
        $("#clientList").html(data);
    });
}

/* LOAD CHAT */
function loadMessages(){
    if(!selectedClient) return;
    $.get("load_chat_csr.php?client_id="+selectedClient, res=>{
        let html="";
        res.forEach(m=>{
          html += `
            <div class="msg ${m.sender_type}">
              <div class="bubble">${m.message ?? ""}
                ${(m.media_path && m.media_type==="image") ? `<br><img src="../${m.media_path}" class="file-img">` : ""}
                ${(m.media_path && m.media_type==="video") ? `<br><video controls class="file-video"><source src="../${m.media_path}"></video>` : ""}
                <div class="meta">${m.created_at}</div>
              </div>
            </div>`;
        });
        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

setInterval(loadMessages,2000);

loadClients();
</script>

</body>
</html>
