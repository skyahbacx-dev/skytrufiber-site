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
<link rel="stylesheet" href="csr_dashboard.css?v=200">
</head>
<body>

<!-- SIDEBAR -->
<div id="sidebar" class="sidebar">
  <div class="sidebar-header">
    <button id="toggleSidebar" class="toggle-btn">☰</button>
    <span class="side-title">Menu</span>
  </div>

  <button class="side-item" onclick="switchTab(document.querySelector('.nav-btn:nth-child(1)'),'all')">💬 Chat Dashboard</button>
  <button class="side-item" onclick="switchTab(document.querySelector('.nav-btn:nth-child(2)'),'mine')">👤 My Clients</button>
  <button class="side-item" onclick="window.location.href='reminders.php'">⏱ Reminders</button>
  <button class="side-item" onclick="window.location.href='survey_responses.php'">📑 Survey Response</button>
  <button class="side-item" onclick="window.location.href='update_profile.php'">👤 Edit Profile</button>
  <button class="side-item logout" onclick="window.location.href='csr_logout.php'">🚪 Logout</button>
</div>

<header class="topnav">
  <img src="AHBALOGO.png" class="nav-logo">
  <h2>CSR DASHBOARD — <?=$csr_fullname?></h2>

  <nav class="nav-buttons">
    <button class="nav-btn active" onclick="switchTab(this,'all')">💬 CHAT DASHBOARD</button>
    <button class="nav-btn" onclick="switchTab(this,'mine')">👤 MY CLIENTS</button>
    <button class="nav-btn" onclick="window.location.href='reminders.php'">⏱ REMINDERS</button>
    <button class="nav-btn" onclick="window.location.href='survey_responses.php'">📑 SURVEY RESPONSE</button>
    <button class="nav-btn" onclick="window.location.href='update_profile.php'">👤 EDIT PROFILE</button>
  </nav>

  <a href="csr_logout.php" class="logout-btn">Logout</a>
</header>

<div id="layout" class="layout">

<section class="client-panel">
  <h3>CLIENTS</h3>
  <input class="search" placeholder="Search clients...">
  <div id="clientList" class="client-list"></div>
</section>

<main class="chat-panel">

  <div class="chat-header">
    <div class="chat-header-left">
      <img id="chatAvatar" src="CSR/lion.PNG" class="chat-avatar">
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

  <div id="chatBox" class="chat-box">
    <p class="placeholder">Select a client to start chatting.</p>
  </div>

  <div id="chatInput" class="chat-input disabled">
    <label for="fileUpload" class="upload-icon">🖼</label>
    <input type="file" id="fileUpload" style="display:none">
    <input type="text" id="msg" placeholder="Type anything....." disabled>
    <button id="sendBtn" class="send-btn" disabled>✈</button>
  </div>

</main>

<aside id="clientInfoPanel" class="client-info-panel">
  <button class="close-info">✖</button>
  <h3>Clients Information</h3>
  <p><strong id="infoName"></strong></p>
  <p id="infoEmail"></p>
  <p>District:</p><p id="infoDistrict"></p>
  <p>Barangay:</p><p id="infoBrgy"></p>
</aside>

</div>

<script>
let currentClient=null;
let csr_user="<?= $csr_user ?>";
let csr_fullname="<?= htmlspecialchars($csr_fullname, ENT_QUOTES) ?>";
let selectedFile=null;

/* SIDEBAR TOGGLE */
const sidebar=document.getElementById("sidebar");
const layout=document.getElementById("layout");
document.getElementById("toggleSidebar").onclick = () =>{
  sidebar.classList.toggle("collapsed");
  layout.classList.toggle("shifted");
};

/* Load Clients */
function loadClients(tab="all"){
  fetch(`load_clients.php?tab=${tab}`)
  .then(r=>r.json())
  .then(list=>{
    const box=document.getElementById("clientList");
    box.innerHTML="";
    list.forEach(c=>{
      let avatar=(c.name[0].toUpperCase()<="M")?"CSR/lion.PNG":"CSR/penguin.PNG";
      box.insertAdjacentHTML("beforeend",`
        <div class="client-item" onclick="openClient(${c.id},'${c.name}')">
          <div class="client-main">
            <img src="${avatar}" class="client-avatar">
            <div class="client-meta">
              <div class="client-name">${c.name}</div>
              <div class="client-sub"><span class="${c.status==='Online'?'online-dot':'offline-dot'}"></span>${c.status}</div>
            </div>
          </div>
        </div>
      `);
    });
  });
}

function openClient(id,name){
  currentClient=id;
  document.getElementById("chatName").innerText=name;
  loadChat();
  setInterval(loadChat,1500);

  document.getElementById("chatInput").classList.remove("disabled");
  document.getElementById("msg").disabled=false;
  document.getElementById("sendBtn").disabled=false;
}

function loadChat(){
  if(!currentClient)return;

  fetch(`../SKYTRUFIBER/load_chat_csr.php?client_id=${currentClient}`)
  .then(r=>r.json())
  .then(rows=>{
    const box=document.getElementById("chatBox");
    box.innerHTML="";

    rows.forEach(m=>{
      let media="";
      if(m.media_path){
        if(m.media_type==="image"){
          media=`<img src="../${m.media_path}" class="file-img">`;
        } else {
          media=`<video src="../${m.media_path}" controls class="file-img"></video>`;
        }
      }

      const side=(m.sender_type==="csr")?"csr":"client";

      box.insertAdjacentHTML("beforeend",`
        <div class="msg ${side}">
          <div class="bubble">
            ${m.message||""}<br>${media}
            <div class="meta">${m.created_at}</div>
          </div>
        </div>
      `);
    });

    box.scrollTop=box.scrollHeight;
  });
}

document.getElementById("sendBtn").onclick = sendMsg;
document.getElementById("msg").addEventListener("keyup",e=>{if(e.key==="Enter")sendMsg();});

function sendMsg(){
  const text=document.getElementById("msg").value.trim();
  if(!text)return;

  const fd=new FormData();
  fd.append("client_id",currentClient);
  fd.append("sender_type","csr");
  fd.append("csr_user",csr_user);
  fd.append("csr_fullname",csr_fullname);
  fd.append("message",text);

  fetch("../SKYTRUFIBER/save_chat_csr.php",{method:"POST",body:fd})
  .then(()=>{document.getElementById("msg").value="";loadChat();});
}

loadClients();
</script>
</body>
</html>
