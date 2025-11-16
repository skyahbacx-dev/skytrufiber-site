<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];
$csr_fullname = $_SESSION['csr_fullname'] ?? $csr_user;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=10">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
.chat-box{
  background: url("AHBALOGO.png") center center no-repeat;
  background-size: 320px;
  opacity: 0.97;
}
.photo-preview-group{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  padding:8px 16px;
}
.photo-item{
  width:110px;height:110px;border-radius:10px;
  border:1px solid #ccc;position:relative;background:#f0f0f0;
}
.photo-item img,.photo-item video{
  width:100%;height:100%;object-fit:cover;border-radius:10px;
}
.remove-photo{
  position:absolute;top:3px;right:5px;
  background:red;color:#fff;border-radius:50%;
  width:22px;height:22px;text-align:center;cursor:pointer;font-size:14px;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
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

<!-- TOP NAVBAR -->
<header class="topnav">
  <img src="AHBALOGO.png" class="nav-logo">
  <h2>CSR DASHBOARD — <?= htmlspecialchars($csr_fullname) ?></h2>

  <nav class="nav-buttons">
      <button class="nav-btn active" onclick="window.location='csr_dashboard.php'">💬 CHAT DASHBOARD</button>
      <button class="nav-btn" onclick="window.location='my_clients.php'">👥 MY CLIENTS</button>
      <button class="nav-btn" onclick="window.location='reminders.php'">⏱ REMINDERS</button>
      <button class="nav-btn" onclick="window.location='survey_responses.php'">📑 SURVEY RESPONSE</button>
      <button class="nav-btn" onclick="window.location='update_profile.php'">👤 EDIT PROFILE</button>
  </nav>

  <a href="csr_logout.php" class="logout-btn">Logout</a>
</header>

<!-- PAGE LAYOUT -->
<div class="layout">

  <!-- CLIENT LIST -->
  <section class="client-panel">
    <h3>CLIENTS</h3>
    <input class="search" placeholder="Search clients...">
    <div id="clientList" class="client-list"></div>
  </section>

  <!-- CHAT PANEL -->
  <main class="chat-panel">

    <div class="chat-header">
      <div class="chat-header-left">
        <img id="chatAvatar" class="chat-avatar" src="lion.PNG">
        <div>
          <div id="chatName" class="chat-name">Select a client</div>
          <div class="chat-status">
            <span id="statusDot" class="status-dot offline"></span>
            <span id="chatStatus">---</span>
          </div>
        </div>
      </div>
      <button id="infoBtn" class="info-btn">ⓘ</button>
    </div>

    <div id="chatBox" class="chat-box"><p class="placeholder">Select a client to start chatting.</p></div>

    <div id="previewGroup" class="photo-preview-group" style="display:none;"></div>

    <div id="chatInput" class="chat-input disabled">
      <label for="fileUpload" class="upload-icon">📁</label>
      <input type="file" id="fileUpload" accept="image/*,video/*" multiple style="display:none">
      <input type="text" id="msg" placeholder="Type a message..." disabled>
      <button id="sendBtn" class="send-btn" disabled>✈</button>
    </div>

  </main>

  <!-- CLIENT INFO PANEL -->
  <aside id="clientInfoPanel" class="client-info-panel">
    <button class="close-info">✖</button>
    <h3>Client Information</h3>
    <p><strong id="infoName"></strong></p>
    <p id="infoEmail"></p>
    <p>District:</p><p id="infoDistrict"></p>
    <p>Barangay:</p><p id="infoBrgy"></p>
  </aside>

</div>

<script>
let currentClient = null;
let selectedFiles = [];
let canChat = false;

/* Toggle sidebar */
function toggleSidebar(){
  document.getElementById("sidebar").classList.toggle("collapsed");
  document.querySelector(".topnav").classList.toggle("collapsed");
  document.querySelector(".layout").classList.toggle("collapsed");
}

/* Load Clients */
function loadClients(){
  $.get("client_list.php", data => $("#clientList").html(data));
}

/* Load Chat */
function loadChat(){
  if(!currentClient) return;

  $.get("load_chat_csr.php?client_id="+currentClient, res=>{
    const box = $("#chatBox");
    box.html("");

    res.forEach(m=>{
      let mediaHtml = "";
      if(m.media_path){
        if(m.media_type==="image")
          mediaHtml = `<img src="${m.media_path}" class="file-img">`;
        else
          mediaHtml = `<video controls class="file-img"><source src="${m.media_path}"></video>`;
      }

      box.append(`
        <div class="msg ${m.sender_type}">
          <div class="bubble">${m.message||""}<br>${mediaHtml}
          <div class="meta">${m.created_at}</div></div>
        </div>
      `);
    });

    box.scrollTop(box[0].scrollHeight);
  });
}

/* Select Client */
function openClient(id, name, avatar){
  currentClient = id;
  $("#chatName").text(name);
  $("#chatAvatar").attr("src",avatar);
  $("#chatInput").removeClass("disabled");
  $("#msg,#sendBtn").prop("disabled",false);
  loadChat();
}

/* File preview */
$("#fileUpload").on("change", function(){
  selectedFiles = Array.from(this.files);
  const preview = $("#previewGroup");
  preview.html("").show();

  selectedFiles.forEach((file,i)=>{
    const url = URL.createObjectURL(file);
    const item = `
      <div class="photo-item">
        <span class="remove-photo" onclick="removePreview(${i})">✖</span>
        ${file.type.startsWith("image") ? `<img src="${url}">` :
        `<video src="${url}" muted></video>`}
      </div>`;
    preview.append(item);
  });
});
function removePreview(i){
  selectedFiles.splice(i,1);
  if(selectedFiles.length===0){
    $("#previewGroup").hide();
    $("#fileUpload").val("");
  }
  $("#previewGroup").children().eq(i).remove();
}

/* Send Message */
$("#sendBtn").on("click", ()=>{
  const text = $("#msg").val().trim();
  if(!text && selectedFiles.length===0) return;

  const fd = new FormData();
  fd.append("message",text);
  fd.append("client_id",currentClient);
  fd.append("csr_fullname","<?= htmlspecialchars($csr_fullname) ?>");
  selectedFiles.forEach(f=> fd.append("files[]",f));

  $.ajax({
    url:"save_chat_csr.php",
    method:"POST",
    data:fd,
    processData:false,
    contentType:false,
    success:()=>{
      $("#msg").val("");
      selectedFiles = [];
      $("#previewGroup").hide().html("");
      $("#fileUpload").val("");
      loadChat();
    }
  });
});

setInterval(loadChat,2500);
loadClients();
</script>

</body>
</html>
