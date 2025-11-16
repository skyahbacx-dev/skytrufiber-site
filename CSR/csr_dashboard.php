<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csrUser = $_SESSION["csr_user"];
$csrFullName = $_SESSION["csr_fullname"] ?? $csrUser;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?php echo $csrFullName; ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=55">
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


<!-- ===== TOP NAV BAR ===== -->
<div class="topnav">
    <div style="display:flex; align-items:center; gap:10px;">
        <img src="AHBALOGO.png" class="nav-logo">
        <h2>CSR DASHBOARD — <?php echo strtoupper($csrUser) ?></h2>
    </div>
</div>

<!-- ===== MAIN LAYOUT ===== -->
<div class="layout">

    <div class="client-panel">
        <h3>CLIENTS</h3>
        <input type="text" class="search" placeholder="Search..." onkeyup="filterClients(this.value)">
        <div id="clientList" class="client-list"></div>
    </div>

    <div class="chat-panel">
        <div class="chat-header">
            <div style="display:flex; align-items:center; gap:10px;">
                <img id="chatAvatar" src="CSR/lion.PNG" class="chat-avatar">
                <div>
                    <div id="chatName" class="chat-name">Select a client</div>
                    <div id="chatStatus" class="chat-status">
                        <span id="statusDot" class="status-dot offline"></span> ---
                    </div>
                </div>
            </div>
        </div>

        <div id="chatMessages" class="chat-box">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <div id="uploadPreview" class="photo-preview-group" style="display:none;"></div>

        <div class="chat-input">
            <label class="upload-icon" for="fileUpload">📎</label>
            <input type="file" id="fileUpload" multiple accept="image/*,video/*" style="display:none;">
            <input type="text" id="messageInput" placeholder="Type a message..." disabled>
            <button class="send-btn" id="sendBtn">✈</button>
        </div>
    </div>

</div>

<!-- IMAGE FULLSCREEN VIEW -->
<div id="imageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.8); justify-content:center; align-items:center;">
    <img id="modalImg" style="max-width:95%; max-height:95%; border-radius:12px;">
</div>

<script>
let selectedClient = 0;
let selectedFiles = [];

/* SIDEBAR */
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("collapsed");
}

/* LOAD CLIENTS */
function loadClients(){
    $.get("client_list.php", function(res){
        $("#clientList").html(res);
    });
}

/* OPEN CHAT */
function openClient(id, name){
    selectedClient=id;
    $("#chatName").text(name);
    $("#messageInput").prop("disabled",false);
    loadMessages();
}

/* LOAD MESSAGES */
function loadMessages(){
    if(!selectedClient) return;

    $.get("load_chat_csr.php?client_id="+selectedClient, function(res){
        let html="";
        res.forEach(m => {
            let side = (m.sender_type==="csr") ? "csr" : "client";

            html += `
            <div class="msg ${side}">
                <div class="bubble">
                    ${m.message ? m.message : ""}<br>
            `;

            if(m.media_path){
                const filePath = `/CSR/${m.media_path}`;
                if(m.media_type === "image"){
                    html += `<img src="${filePath}" class="file-img" onclick="previewImg('${filePath}')">`;
                } else {
                    html += `<video controls class="file-video"><source src="${filePath}"></video>`;
                }
            }

            html += `
                   <div class="meta">${m.created_at}</div>
                </div>
            </div>`;
        });

        $("#chatMessages").html(html);
        $("#chatMessages").scrollTop($("#chatMessages")[0].scrollHeight);
    });
}

/* MULTIPLE FILE HANDLING */
$("#fileUpload").on("change", function(){
    for(let f of this.files){ selectedFiles.push(f); }
    displayPreview();
});

function displayPreview(){
    let div=$("#uploadPreview");
    div.html("").show();

    selectedFiles.forEach((file,i)=>{
        let url=URL.createObjectURL(file);
        div.append(`
        <div class="photo-item">
            <span class="remove-photo" onclick="removePreview(${i})">✖</span>
            <img src="${url}">
        </div>`);
    });
}

function removePreview(i){
    selectedFiles.splice(i,1);
    displayPreview();
    if(selectedFiles.length===0) $("#uploadPreview").hide();
}

/* SEND MESSAGE */
$("#sendBtn").click(function(){
    let msg=$("#messageInput").val().trim();
    if(!msg && selectedFiles.length===0) return;

    let fd = new FormData();
    fd.append("client_id",selectedClient);
    fd.append("csr_fullname","<?php echo $csrFullName ?>");
    fd.append("message", msg);

    selectedFiles.forEach((file,i)=>{ fd.append("file"+i, file); });

    $.ajax({
        url:"save_chat_csr.php",
        method:"POST",
        data:fd,
        processData:false,
        contentType:false,
        success:function(){
            $("#messageInput").val("");
            selectedFiles = [];
            $("#uploadPreview").hide().html("");
            loadMessages();
        }
    });
});

/* IMAGE VIEW */
function previewImg(src){
    $("#modalImg").attr("src",src);
    $("#imageModal").css("display","flex");
}
$("#imageModal").click(()=>$("#imageModal").hide());

setInterval(loadMessages,2000);
loadClients();
</script>

</body>
</html>
