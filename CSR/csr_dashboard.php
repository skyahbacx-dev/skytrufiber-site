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
<link rel="stylesheet" href="csr_dashboard.css?v=38">
</head>
<body>

<!-- ===== SIDEBAR ===== -->
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

<!-- ===== TOP NAV ===== -->
<header class="topnav" id="topnav" style="margin-left:230px;">
  <img src="AHBALOGO.png" class="nav-logo">
  <h2>CSR DASHBOARD — <?= htmlspecialchars($csr_fullname) ?></h2>
</header>

<!-- ===== MAIN LAYOUT ===== -->
<div class="layout" id="layout" style="margin-left:230px;">

<section class="client-panel">
  <h3>CLIENTS</h3>
  <input class="search" placeholder="Search clients...">
  <div id="clientList" class="client-list"></div>
</section>

<main class="chat-panel">
  <div class="chat-header">
    <img id="chatAvatar" src="lion.PNG" class="chat-avatar">
    <div>
      <div id="chatName" class="chat-name">Select a client</div>
      <div class="chat-status">
        <span id="statusDot" class="status-dot offline"></span>
        <span id="chatStatus">---</span>
      </div>
    </div>
    <button id="infoBtn" class="info-btn">ⓘ</button>
  </div>

  <div id="chatBox" class="chat-box">
    <p class="placeholder">Select a client to start chatting.</p>
  </div>

  <div id="chatInput" class="chat-input disabled">
    <label for="fileUpload" class="upload-icon">📎</label>
    <input type="file" id="fileUpload" style="display:none">
    <input type="text" id="msg" placeholder="Type anything....." disabled>
    <button id="sendBtn" class="send-btn" disabled>✈</button>
  </div>
</main>
</div>

<script>
let currentClient = null;
let canChat = false;
let selectedFile = null;
let csr_user = "<?= $csr_user ?>";
let csr_fullname = "<?= htmlspecialchars($csr_fullname, ENT_QUOTES) ?>";
let refreshTimer = null;

/* Load Clients */
function loadClients(tab="all"){
  fetch(`/CSR/load_clients.php?tab=${tab}`)
    .then(r=>r.json())
    .then(list=>{
      const box=document.getElementById("clientList");
      box.innerHTML="";
      if(!list.length){
        box.innerHTML="<p>No clients found.</p>";
        return;
      }

      list.forEach(c=>{
        box.insertAdjacentHTML("beforeend",`
          <div class="client-item" onclick="openClient(${c.id}, '${c.name}')">
            <div class="client-main">
              <img src="lion.PNG" class="client-avatar">
              <div>
                <div class="client-name">${c.name}</div>
                <div class="client-sub">${c.status}</div>
              </div>
            </div>
          </div>`);
      });
    });
}

function openClient(id,name){
  currentClient=id;
  document.getElementById("chatName").innerText=name;

  loadChat();
  if(refreshTimer) clearInterval(refreshTimer);
  refreshTimer=setInterval(loadChat,2000);

  document.getElementById("msg").disabled=false;
  document.getElementById("sendBtn").disabled=false;
  document.getElementById("chatInput").classList.remove("disabled");
}

/* Load Chat */
function loadChat(){
  if(!currentClient)return;
  fetch(`/CSR/load_chat_csr.php?client_id=${currentClient}`)
    .then(r=>r.json())
    .then(rows=>{
      const box=document.getElementById("chatBox");
      box.innerHTML="";
      if(!rows.length){
        box.innerHTML="<p class='placeholder'>No messages yet.</p>";
        return;
      }
      rows.forEach(m=>{
        box.insertAdjacentHTML("beforeend",`
          <div class="msg ${m.sender_type}">
            <div class="bubble">${m.message}
            <div class="meta">${m.created_at}</div>
            </div>
          </div>`);
      });
      box.scrollTop=box.scrollHeight;
    });
}

/* Send Message */
document.getElementById("sendBtn").addEventListener("click",sendMsg);

function sendMsg(){
  const text=document.getElementById("msg").value.trim();
  if(!text)return;

  const fd=new FormData();
  fd.append("sender_type","csr");
  fd.append("message",text);
  fd.append("client_id",currentClient);
  fd.append("csr_user",csr_user);
  fd.append("csr_fullname",csr_fullname);

  fetch(`/CSR/save_chat_csr.php`,{method:"POST",body:fd})
    .then(r=>r.json())
    .then(()=>{document.getElementById("msg").value="";loadChat();});
}

/* Toggle Sidebar */
const sidebar=document.getElementById("sidebar");
const layout=document.getElementById("layout");
const topnav=document.getElementById("topnav");
document.getElementById("toggleSidebar").onclick=()=>{
  sidebar.classList.toggle("collapsed");
  const width = sidebar.classList.contains("collapsed") ? "65px" : "230px";
  layout.style.marginLeft=width;
  topnav.style.marginLeft=width;
};

loadClients();
</script>
</body>
</html>
