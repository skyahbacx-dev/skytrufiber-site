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

/* ================= AJAX HANDLERS ================== */
if (isset($_GET['ajax'])) {
    header("Content-Type: application/json");

    if ($_GET['ajax'] === 'load_clients') {
        $tab = $_GET['tab'] ?? 'all';

        if ($tab === 'mine') {
            $stmt = $conn->prepare("SELECT * FROM clients WHERE assigned_csr = :csr ORDER BY name ASC");
            $stmt->execute([':csr'=>$csr_user]);
        } else {
            $stmt = $conn->query("SELECT * FROM clients ORDER BY name ASC");
        }

        $rows=[];
        while($r=$stmt->fetch(PDO::FETCH_ASSOC)) {
            $last=$r['last_active'] ?? null;
            $status = ($last && strtotime($last) > time()-60) ? "Online" : "Offline";
            $r['status']=$status;
            $rows[]=$r;
        }
        echo json_encode($rows);
        exit;
    }

    if ($_GET['ajax']==='get_client' && isset($_GET['id'])) {
        $id=(int)$_GET['id'];
        $stmt=$conn->prepare("SELECT * FROM clients WHERE id=:id LIMIT 1");
        $stmt->execute([':id'=>$id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit;
    }

    if ($_GET['ajax']==='assign' && isset($_GET['id'])) {
        $id=(int)$_GET['id'];
        $stmt=$conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id=:id");
        $stmt->execute([':csr'=>$csr_user,':id'=>$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($_GET['ajax']==='unassign' && isset($_GET['id'])) {
        $id=(int)$_GET['id'];
        $stmt=$conn->prepare("UPDATE clients SET assigned_csr=NULL WHERE id=:id AND assigned_csr=:csr");
        $stmt->execute([':id'=>$id,':csr'=>$csr_user]);
        echo json_encode(['ok'=>true]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=100">
</head>

<body>

<header class="topnav">
  <img src="AHBALOGO.png" class="nav-logo">
  <h2>CSR DASHBOARD — <?=$csr_fullname?></h2>

  <nav class="nav-buttons">
    <button class="nav-btn active" onclick="switchTab(this,'all')">💬 CHAT DASHBOARD</button>
    <button class="nav-btn" onclick="switchTab(this,'mine')">👤 MY CLIENTS</button>
    <button class="nav-btn" onclick="window.location.href='reminders.php'">⏱ REMINDERS</button>
    <button class="nav-btn" onclick="window.location.href='survey_responses.php'">📑 SURVEY RESPONSE</button>
    <button class="nav-btn" onclick="window.location.href='update_profile.php'">👤 EDIT PROFILE</button>
    <a href="csr_logout.php" class="logout-btn">Logout</a>
  </nav>
</header>

<div class="layout">

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
  </div>

  <div id="chatBox" class="chat-box"><p class="placeholder">Select a client to start chatting.</p></div>

  <div id="uploadPreview" class="photo-preview-group" style="display:none;"></div>

  <div id="chatInput" class="chat-input disabled">
    <label for="fileUpload" class="upload-icon">🖼</label>
    <input type="file" id="fileUpload" style="display:none" accept="image/*,video/*">
    <input type="text" id="msg" placeholder="Type anything..." disabled>
    <button id="sendBtn" disabled>✈</button>
  </div>
</main>
</div>


<script>
let currentClient=null;
let selectedFile=null;
let csr_user="<?= $csr_user ?>";
let csr_fullname="<?= htmlspecialchars($csr_fullname, ENT_QUOTES) ?>";
let refreshTimer=null;

/* Load client list */
function loadClients(tab='all'){
  fetch(`/CSR/csr_dashboard.php?ajax=load_clients&tab=${tab}`)
    .then(r=>r.json())
    .then(clients=>{
      const list=document.getElementById("clientList");
      list.innerHTML="";

      clients.forEach(c=>{
        const avatar=(c.name && c.name[0].toUpperCase()<="M")?"CSR/lion.PNG":"CSR/penguin.PNG";

        list.insertAdjacentHTML("beforeend",`
          <div class="client-item" onclick="openClient(${c.id}, '${c.name}')">
            <div class="client-main">
              <img src="${avatar}" class="client-avatar">
              <div class="client-meta">
                <div class="client-name">${c.name}</div>
                <div class="client-sub">${c.status} • ${c.assigned_csr?("CSR: "+c.assigned_csr):"Unassigned"}</div>
              </div>
            </div>
          </div>`);
      });
    });
}

function switchTab(btn,tab){
  document.querySelectorAll(".nav-btn").forEach(b=>b.classList.remove("active"));
  btn.classList.add("active");
  loadClients(tab);
}

/* Open client chat */
function openClient(id,name){
  currentClient=id;
  document.getElementById("chatName").innerText=name;
  document.getElementById("msg").disabled=false;
  document.getElementById("sendBtn").disabled=false;

  loadChat();
  if(refreshTimer)clearInterval(refreshTimer);
  refreshTimer=setInterval(loadChat,1500);
}

/* Load chat messages */
function loadChat(){
  fetch(`/CSR/load_chat_csr.php?client_id=${currentClient}`)
    .then(r=>r.json())
    .then(rows=>{
      const box=document.getElementById("chatBox");
      box.innerHTML="";

      rows.forEach(m=>{
        const align = m.sender_type === "csr" ? "msg-out" : "msg-in";
        let media="";

        if(m.media_path){
          if(m.media_type==="image"){
            media=`<img src="../${m.media_path}" class="file-img">`;
          } else {
            media=`<video src="../${m.media_path}" class="file-video" controls></video>`;
          }
        }

        box.insertAdjacentHTML("beforeend",`
          <div class="msg ${align}">
            <div class="bubble">
              ${m.message?m.message:""}<br>${media}
              <div class="meta">${m.created_at}</div>
            </div>
          </div>`);
      });

      box.scrollTop=box.scrollHeight;
    });
}

/* Upload preview */
document.getElementById("fileUpload").addEventListener("change",()=>{
  const file=document.getElementById("fileUpload").files[0];
  if(!file)return;

  selectedFile=file;
  const preview=document.getElementById("uploadPreview");
  preview.style.display="flex";
  preview.innerHTML=`<div><span onclick="cancelUpload()" style="cursor:pointer">✖</span><p>${file.name}</p></div>`;
});

function cancelUpload(){
  selectedFile=null;
  document.getElementById("fileUpload").value="";
  document.getElementById("uploadPreview").style.display="none";
}

/* Send message */
document.getElementById("sendBtn").addEventListener("click",sendMsg);
document.getElementById("msg").addEventListener("keypress",e=>{if(e.key==="Enter")sendMsg();});

function sendMsg(){
  const text=document.getElementById("msg").value.trim();
  if(!text && !selectedFile)return;

  const fd=new FormData();
  fd.append("sender_type","csr");
  fd.append("message",text);
  fd.append("client_id",currentClient);
  fd.append("csr_fullname",csr_fullname);
  fd.append("csr_user",csr_user);
  if(selectedFile)fd.append("file",selectedFile);

  fetch(`/CSR/save_chat_csr.php`,{method:"POST",body:fd})
    .then(r=>r.json())
    .then(res=>{
      document.getElementById("msg").value="";
      cancelUpload();
      loadChat();
    });
}

loadClients();
</script>

</body>
</html>
