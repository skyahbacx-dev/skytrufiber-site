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

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    if ($_GET['ajax'] === 'load_clients') {
        $tab = $_GET['tab'] ?? 'all';

        if ($tab === 'mine') {
            $stmt = $conn->prepare("SELECT * FROM clients WHERE assigned_csr = :csr ORDER BY name ASC");
            $stmt->execute([':csr' => $csr_user]);
        } else {
            $stmt = $conn->query("SELECT * FROM clients ORDER BY name ASC");
        }

        $rows = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $last = $r['last_active'] ?? null;
            $status = ($last && strtotime($last) > time() - 60) ? 'Online' : 'Offline';
            $r['status'] = $status;
            $rows[] = $r;
        }
        echo json_encode($rows);
        exit;
    }

    if ($_GET['ajax'] === 'get_client' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit;
    }

    if ($_GET['ajax'] === 'assign' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id AND (assigned_csr IS NULL OR assigned_csr = '')");
        $stmt->execute([':csr' => $csr_user, ':id' => $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($_GET['ajax'] === 'unassign' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :id AND assigned_csr = :csr");
        $stmt->execute([':id' => $id, ':csr' => $csr_user]);
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['error' => 'bad request']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=99">
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
  </nav>

  <a href="csr_logout.php" class="logout-btn">Logout</a>
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
let currentClient  = null;
let canChat        = false;
let selectedFile   = null;
let csr_user       = "<?= $csr_user ?>";
let csr_fullname   = "<?= htmlspecialchars($csr_fullname, ENT_QUOTES) ?>";
let refreshTimer   = null;

/* Toggle info panel */
document.getElementById("infoBtn")?.addEventListener('click',()=>document.getElementById("clientInfoPanel").classList.add("active"));
document.querySelector(".close-info").onclick = ()=>document.getElementById("clientInfoPanel").classList.remove("active");

/* Load Clients */
function loadClients(tab='all'){
  fetch(`csr_dashboard.php?ajax=load_clients&tab=${tab}`)
    .then(r=>r.json())
    .then(clients=>{
      const list=document.getElementById("clientList");
      list.innerHTML="";
      if(!clients.length){
        list.innerHTML="<p>No clients found.</p>";
        return;
      }

      clients.forEach(c=>{
        const avatar=(c.name && c.name[0].toUpperCase()<="M") ? "CSR/lion.PNG" : "CSR/penguin.PNG";

        let actionBtn='';
        if(!c.assigned_csr){
          actionBtn=`<button class="pill green" onclick="event.stopPropagation();assignClient(${c.id});">＋</button>`;
        } else if(c.assigned_csr===csr_user){
          actionBtn=`<button class="pill red" onclick="event.stopPropagation();unassignClient(${c.id});">−</button>`;
        } else {
          actionBtn=`<button class="pill gray" disabled>🔒</button>`;
        }

        list.insertAdjacentHTML("beforeend",`
          <div class="client-item ${c.assigned_csr && c.assigned_csr!==csr_user?'locked':''}"
               onclick="openClient(${c.id}, '${(c.name||'').replace(/'/g,"\\'")}')">
            <div class="client-main">
              <img src="${avatar}" class="client-avatar">
              <div class="client-meta">
                <div class="client-name">${c.name||''}</div>
                <div class="client-sub">
                  <span class="${c.status==='Online'?'online-dot':'offline-dot'}"></span>
                  ${c.status || 'Offline'} • ${c.assigned_csr?`CSR: ${c.assigned_csr}`:'Unassigned'}
                </div>
              </div>
            </div>
            <div class="client-actions">${actionBtn}</div>
          </div>`);
      });
    });
}

function switchTab(btn,tab){
  document.querySelectorAll(".nav-btn").forEach(el=>el.classList.remove("active"));
  btn.classList.add("active");
  loadClients(tab);
}

/* Assign and unassign */
function assignClient(id){
  fetch(`csr_dashboard.php?ajax=assign&id=${id}`)
  .then(()=>loadClients());
}

function unassignClient(id){
  if(!confirm("Unassign this client?"))return;
  fetch(`csr_dashboard.php?ajax=unassign&id=${id}`)
  .then(()=>loadClients());
}

/* Open client chat */
function openClient(id,name){
  currentClient=id;
  document.getElementById("chatName").innerText=name;

  const avatar=(name && name[0].toUpperCase()<="M")?"CSR/lion.PNG":"CSR/penguin.PNG";
  document.getElementById("chatAvatar").src=avatar;

  fetch(`csr_dashboard.php?ajax=get_client&id=${id}`)
    .then(r=>r.json())
    .then(c=>{
      canChat = (!c.assigned_csr || c.assigned_csr===csr_user);

      document.getElementById("chatStatus").innerText =
        !c.assigned_csr ? "Unassigned — you can claim this client." :
        c.assigned_csr===csr_user ? "Assigned to you" :
        "Assigned to CSR: "+c.assigned_csr;

      document.getElementById("statusDot").className =
        "status-dot " + (c.status==="Online"?"online":"offline");

      document.getElementById("infoName").innerText=c.name;
      document.getElementById("infoEmail").innerText=c.email;
      document.getElementById("infoDistrict").innerText=c.district;
      document.getElementById("infoBrgy").innerText=c.barangay;

      document.getElementById("chatInput").classList.toggle("disabled",!canChat);
      document.getElementById("msg").disabled=!canChat;
      document.getElementById("sendBtn").disabled=!canChat;

      loadChat();
      if(refreshTimer)clearInterval(refreshTimer);
      refreshTimer=setInterval(loadChat,3000);
    });
}

/* Load Chat – now uses load_chat_csr.php */
function loadChat(){
  if(!currentClient)return;
  fetch(`load_chat_csr.php?client_id=${currentClient}`)
    .then(r=>r.json())
    .then(rows=>{
      const box=document.getElementById("chatBox");
      box.innerHTML="";

      if(!rows.length){
        box.innerHTML='<p class="placeholder">No messages yet.</p>';
        return;
      }

      rows.forEach(m=>{
        let mediaHTML='';
        if(m.media_path){
          if(m.media_type==='image'){
            mediaHTML = `<br><img src="../${m.media_path}" class="file-img">`;
          } else {
            mediaHTML = `<br><video src="../${m.media_path}" class="file-video" controls></video>`;
          }
        }

        const align = (m.sender_type==='csr') ? 'msg-out' : 'msg-in';

        box.insertAdjacentHTML("beforeend",`
          <div class="msg ${align}">
            <div class="bubble">
              ${m.message||''}${mediaHTML}
              <div class="meta">${m.created_at}</div>
            </div>
          </div>`);
      });

      box.scrollTop=box.scrollHeight;
    });
}

/* File Preview */
document.getElementById("fileUpload").addEventListener("change",function(){
  const file=this.files[0];
  if(!file)return;

  selectedFile=file;
  const url=URL.createObjectURL(file);

  const preview=document.getElementById("uploadPreview");
  preview.style.display="flex";
  preview.innerHTML=`<div class="photo-item">
      <span class="remove-photo">✖</span>
      <img src="${url}">
    </div>`;

  document.querySelector(".remove-photo").onclick=()=>{
    selectedFile=null;
    document.getElementById("fileUpload").value="";
    preview.style.display="none";
  };
});

/* Send Chat – now uses save_chat_csr.php */
document.getElementById("sendBtn").addEventListener("click",sendMsg);
document.getElementById("msg").addEventListener("keyup",e=>{if(e.key==="Enter")sendMsg();});

function sendMsg(){
  if(!currentClient||!canChat)return;
  const text=document.getElementById("msg").value.trim();
  if(!text && !selectedFile)return;

  const fd=new FormData();
  fd.append("sender_type","csr");
  fd.append("message",text);
  fd.append("client_id",currentClient);
  fd.append("csr_user",csr_user);
  fd.append("csr_fullname",csr_fullname);
  if(selectedFile)fd.append("file",selectedFile);

  fetch("save_chat_csr.php",{method:"POST",body:fd})
    .then(r=>r.json())
    .then(res=>{
      if(res.status==="ok"){
        document.getElementById("msg").value="";
        selectedFile=null;
        document.getElementById("fileUpload").value="";
        document.getElementById("uploadPreview").style.display="none";
        loadChat();
      } else alert(res.msg||"Send failed");
    });
}

loadClients();
</script>
</body>
</html>
