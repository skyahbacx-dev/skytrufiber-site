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
<title>CSR Dashboard - <?php echo $csrUser; ?></title>
<link rel="stylesheet" href="csr_dashboard.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="menu-title">MENU</div>
    <button class="menu-btn active" onclick="window.location='csr_dashboard.php'">💬 Chat Dashboard</button>
    <button class="menu-btn" onclick="window.location='my_clients.php'">👥 My Clients</button>
    <button class="menu-btn" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="menu-btn" onclick="window.location='survey_responses.php'">📄 Survey Responses</button>
    <button class="menu-btn" onclick="window.location='update_profile.php'">👤 Edit Profile</button>
    <button class="logout-btn" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>

<!-- NAVBAR -->
<div class="topnav">
    <img src="upload/AHBALOGO.png" class="nav-logo">
    <h2>CSR DASHBOARD — <?php echo strtoupper($csrUser); ?></h2>

    <div class="nav-buttons">
        <button class="nav-btn active">💬 CHAT DASHBOARD</button>
        <button class="nav-btn" onclick="window.location='my_clients.php'">👥 MY CLIENTS</button>
        <button class="nav-btn" onclick="window.location='reminders.php'">⏱ REMINDERS</button>
        <button class="nav-btn" onclick="window.location='survey_responses.php'">📄 SURVEY RESPONSE</button>
        <button class="nav-btn" onclick="window.location='update_profile.php'">👤 EDIT PROFILE</button>
        <a href="csr_logout.php" class="logout-nav">Logout</a>
    </div>
</div>

<!-- MAIN LAYOUT -->
<div class="layout">
    <!-- LEFT CLIENT LIST -->
    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input type="text" id="search" placeholder="Search clients...">
        <div id="clientList"></div>
    </div>

    <!-- CHAT AREA -->
    <div class="chat-panel">
        <div class="chat-header">
            <img id="chatAvatar" class="chat-avatar">
            <div>
                <div class="chat-name" id="chatName">Select a client</div>
                <div class="chat-status"><span class="status-dot offline"></span> ---</div>
            </div>

            <button class="info-btn" onclick="toggleInfo()">ℹ</button>
        </div>

        <div class="chat-box" id="chatMessages">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <!-- Upload Preview -->
        <div id="previewContainer" class="preview-wrapper"></div>

        <div class="chat-input">
            <label for="upload" class="gallery-btn">🖼</label>
            <input type="file" id="upload" multiple accept="image/*">

            <input type="text" id="messageInput" placeholder="Type a message..." disabled>
            <button id="sendBtn" class="send-btn">✈</button>
        </div>
    </div>

    <!-- SLIDING CLIENT INFO PANEL -->
    <div class="client-info" id="clientInfoPanel"></div>
</div>

<script>
let selectedClient = 0;

// Load client list
function loadClients(){
    $.get("client_list.php", function(data){
        $("#clientList").html(data);
    });
}

// Load messages
function loadMessages(){
    if (!selectedClient) return;
    $.get("load_chat_csr.php?client_id=" + selectedClient, function(res){
        let html = "";
        res.forEach(m => {
            let side = (m.sender_type === "csr") ? "csr" : "client";
            html += `<div class="msg ${side}">
                        ${m.media_path ? `<img src="${m.media_path}" class="chat-image">` : ""}
                        <div class="bubble">${m.message}</div>
                        <div class="meta">${m.created_at}</div>
                    </div>`;
        });
        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

// SEND MESSAGE
$("#sendBtn").click(function(){
    let message = $("#messageInput").val();
    let files = $("#upload")[0].files;

    let formData = new FormData();
    formData.append("client_id", selectedClient);
    formData.append("message", message);
    formData.append("csr_fullname", "<?php echo $csrFullName;?>");

    for(let i=0;i<files.length;i++){
        formData.append("files[]", files[i]);
    }

    $.ajax({
        url: "save_chat_csr.php",
        method: "POST",
        data: formData,
        contentType:false,
        processData:false,
        success: function(){
            $("#messageInput").val("");
            $("#upload").val("");
            $("#previewContainer").html("");
            loadMessages();
        }
    });
});

setInterval(loadMessages, 2000);
loadClients();

// Preview handler
$("#upload").on("change", function(e){
    $("#previewContainer").html("");
    [...e.target.files].forEach(file => {
        let img = URL.createObjectURL(file);
        $("#previewContainer").append(`<img src="${img}" class="preview-img">`);
    });
});

</script>
</body>
</html>
