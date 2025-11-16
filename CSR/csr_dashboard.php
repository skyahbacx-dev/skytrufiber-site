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
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$csr_fullname = $data['full_name'] ?? $csr_user;
$csr_avatar   = $data['profile_pic'] ?? 'CSR/default_avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=100">
</head>
<body>

<!-- ========== SIDEBAR ========== -->
<div id="sidebar" class="sidebar">
    <button id="toggleSidebar" class="toggle-btn">☰</button>

    <div class="side-title">Menu</div>
    <button class="side-item" onclick="switchTab('all')">💬 Chat Dashboard</button>
    <button class="side-item" onclick="switchTab('mine')">👤 My Clients</button>
    <button class="side-item" onclick="window.location='reminders.php'">⏱ Reminders</button>
    <button class="side-item" onclick="window.location='survey_responses.php'">📑 Survey Response</button>
    <button class="side-item" onclick="window.location='update_profile.php'">👤 Edit Profile</button>
    <button class="side-item logout" onclick="window.location='csr_logout.php'">🚪 Logout</button>
</div>

<!-- ========== TOP NAVBAR ========== -->
<header class="topnav">
  <img src="AHBALOGO.png" class="nav-logo">
  <h2>CSR Dashboard — <?=$csr_fullname?></h2>
</header>

<div class="layout">

<!-- ========== CLIENT PANEL ========== -->
<section class="client-panel">
  <h3>CLIENTS</h3>
  <input class="search" placeholder="Search clients..." onkeyup="searchClients(this.value)">
  <div id="clientList" class="client-list"></div>
</section>

<!-- ========== CHAT PANEL ========== -->
<main class="chat-panel">

  <div class="chat-header">
    <div class="chat-header-left">
      <img id="chatAvatar" src="CSR/lion.PNG" class="chat-avatar">
      <div>
        <div id="chatName" class="chat-name">Select a client</div>
        <div id="chatStatus" class="chat-status">---</div>
      </div>
    </div>
  </div>

  <div id="chatBox" class="chat-box">
    <p class="placeholder">Select a client to start chatting.</p>
  </div>

  <div id="uploadPreview" class="photo-preview-group" style="display:none;"></div>

  <div id="chatInput" class="chat-input disabled">
    <label for="fileUpload" class="upload-icon">🖼</label>
    <input type="file" id="fileUpload" style="display:none">
    <input type="text" id="msg" placeholder="Type a message..." disabled>
    <button id="sendBtn" class="send-btn" disabled>✈</button>
  </div>
</main>

</div>

<script>
let currentClient = null;
let selectedFile = null;
let csr_user = "<?= $csr_user ?>";
let csr_fullname = "<?= htmlspecialchars($csr_fullname, ENT_QUOTES) ?>";

// ========= SIDEBAR TOGGLE =========
document.getElementById("toggleSidebar").onclick = () =>
    document.getElementById("sidebar").classList.toggle("collapsed");

// ========= LOAD CLIENT LIST =========
function loadClients(tab = "all"){
  fetch(`/CSR/load_clients.php?tab=${tab}`)
    .then(res=>res.json())
    .then(clients=>{
      const list=document.getElementById("clientList");
      list.innerHTML="";
      if(!clients.length){
        list.innerHTML="<p>No clients found.</p>";
        return;
      }
      clients.forEach(c=>{
        const avatar = (c.name[0].toUpperCase() <= 'M') ? "CSR/lion.PNG" : "CSR/penguin.PNG";
        list.insertAdjacentHTML("beforeend",`
          <div class="client-item" onclick="openClient(${c.id}, '${c.name}')">
            <div class="client-main">
              <img src="${avatar}" class="client-avatar">
              <div>
                <div class="client-name">${c.name}</div>
                <div class="client-sub"><span class="${c.status==='Online'?'online-dot':'offline-dot'}"></span>${c.status}</div>
              </div>
            </div>
          </div>
        `);
      });
    });
}

function switchTab(tab){ loadClients(tab); }
loadClients();

// ========= LOAD CHAT =========
function openClient(id,name){
  currentClient=id;
  document.getElementById("chatName").innerText=name;
  document.getElementById("chatInput").classList.remove("disabled");
  document.getElementById("msg").disabled=false;
  document.getElementById("sendBtn").disabled=false;

  loadChat();
  setInterval(loadChat,2000);
}

function loadChat(){
  if(!currentClient) return;
  fetch(`/CSR/load_chat_csr.php?client_id=${currentClient}`)
    .then(r=>r.json())
    .then(rows=>{
      const box=document.getElementById("chatBox");
      box.innerHTML="";
      rows.forEach(m=>{
        let media="";
        if(m.media_path){
          if(m.media_type==="image") media=`<img class="file-img" src="../${m.media_path}">`;
          else media=`<video class="file-img" controls src="../${m.media_path}"></video>`;
        }
        box.insertAdjacentHTML("beforeend",`
          <div class="msg ${m.sender_type}">
            <div class="bubble">${m.message||""}${media}
              <div class="meta">${m.created_at}</div>
            </div>
          </div>
        `);
      });
      box.scrollTop=box.scrollHeight;
    });
}

// ========= SEND MESSAGE =========
document.getElementById("sendBtn").onclick = sendMsg;
document.getElementById("msg").addEventListener("keyup",e=>{ if(e.key==="Enter") sendMsg(); });

function sendMsg(){
  const text=document.getElementById("msg").value.trim();
  if(!text && !selectedFile) return;

  const fd=new FormData();
  fd.append("message",text);
  fd.append("client_id",currentClient);
  fd.append("csr_user",csr_user);
  fd.append("csr_fullname",csr_fullname);
  if(selectedFile) fd.append("file",selectedFile);

  fetch(`/CSR/save_chat_csr.php`,{method:"POST",body:fd})
    .then(res=>res.json())
    .then(data=>{
      if(data.status==="ok"){
        selectedFile=null;
        document.getElementById("msg").value="";
        loadChat();
      }
    });
}
</script>
</body>
</html>
