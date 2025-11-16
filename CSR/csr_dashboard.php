<?php
session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csrUser = $_SESSION["csr_username"];
$csrFullName = $_SESSION["csr_fullname"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?php echo $csrFullName; ?></title>
<link rel="stylesheet" href="csr_dashboard.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">×</button>

    <div class="side-title">Menu</div>
    <button class="side-item" onclick="window.location='csr_dashboard.php'">💬 Chat Dashboard</button>
    <button class="side-item" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📄 Survey Responses</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>

    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>

<!-- ===== TOP NAV BAR ===== -->
<div class="topnav">
    <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

    <div style="display:flex;align-items:center;gap:10px;">
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

<!-- ===== MAIN LAYOUT ===== -->
<div class="layout">
    <!-- CLIENT LIST -->
    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input type="text" class="search" placeholder="Search clients...">

        <div class="client-list" id="clientList">
            <!-- Loaded via AJAX -->
        </div>
    </div>

    <!-- CHAT PANEL -->
    <div class="chat-panel">
        <div class="chat-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <img id="chatAvatar" class="chat-avatar">
                <div>
                    <div class="chat-name" id="chatName">Select a client</div>
                    <div class="chat-status"><span class="status-dot offline"></span>---</div>
                </div>
            </div>
        </div>

        <div class="chat-box" id="chatMessages">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <div class="chat-input">
            <input type="text" id="messageInput" placeholder="Type a message..." disabled>
            <button class="send-btn" id="sendBtn">✈</button>
        </div>
    </div>
</div>

<script>
let selectedClient = 0;

// Load Clients
function loadClients() {
    $.get("client_list.php", function(data){
        $("#clientList").html(data);
    });
}

// Load Messages
function loadMessages() {
    if (!selectedClient) return;
    $.get("load_chat_csr.php?client_id="+selectedClient, function(res){
        let html = "";
        res.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";
            html += `
            <div class="msg ${side}">
                <div class="bubble">${m.message}</div>
                <div class="meta">${m.created_at}</div>
            </div>`;
        });
        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

// Send message
$("#sendBtn").click(function(){
    let message = $("#messageInput").val();
    if(!message.trim()) return;

    $.post("save_chat_csr.php", {
        message: message,
        client_id: selectedClient,
        csr_fullname: "<?php echo $csrFullName; ?>"
    }, function(){
        $("#messageInput").val("");
        loadMessages();
    });
});
if(m.media_path){
    if(m.media_type === "image"){
        html += `<img src="${m.media_path}" class="file-img">`;
    }
    if(m.media_type === "video"){
        html += `<video controls class="file-video">
                    <source src="${m.media_path}">
                 </video>`;
    }
}

setInterval(loadMessages, 2000);
loadClients();
</script>

</body>
</html>
