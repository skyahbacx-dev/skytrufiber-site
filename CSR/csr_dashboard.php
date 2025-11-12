<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Get CSR info
$stmt = $conn->prepare("SELECT full_name, profile_pic FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;
$csr_avatar = $row['profile_pic'] ?? 'CSR/default_avatar.png';

/* ========= AJAX HANDLERS ========= */
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    // === Load Clients ===
    if ($_GET['ajax'] === 'load_clients') {
        $tab = $_GET['tab'] ?? 'all';
        if ($tab === 'mine') {
            $stmt = $conn->prepare("SELECT * FROM clients WHERE assigned_csr = :csr ORDER BY name ASC");
            $stmt->execute([':csr' => $csr_user]);
        } else {
            $stmt = $conn->query("SELECT * FROM clients ORDER BY name ASC");
        }

        $clients = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $clients[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'status' => (strtotime($r['last_active']) > time() - 60) ? 'Online' : 'Offline',
                'assigned_csr' => $r['assigned_csr']
            ];
        }
        echo json_encode($clients);
        exit;
    }

    // === Assign / Unassign ===
    if ($_GET['ajax'] === 'toggle_assign' && isset($_POST['client_id'])) {
        $cid = (int)$_POST['client_id'];
        $stmt = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
        $stmt->execute([':id' => $cid]);
        $current = $stmt->fetchColumn();

        if ($current === $csr_user) {
            $stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :id");
            $stmt->execute([':id' => $cid]);
            echo json_encode(['status' => 'unassigned']);
        } elseif (empty($current)) {
            $stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id");
            $stmt->execute([':csr' => $csr_user, ':id' => $cid]);
            echo json_encode(['status' => 'assigned']);
        } else {
            echo json_encode(['status' => 'locked']);
        }
        exit;
    }

    // === Load Chat ===
    if ($_GET['ajax'] === 'load_chat' && isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];
        $stmt = $conn->prepare("
            SELECT c.name AS client, c.assigned_csr, ch.*
            FROM chat ch
            JOIN clients c ON ch.client_id = c.id
            WHERE ch.client_id = :cid
            ORDER BY ch.created_at ASC
        ");
        $stmt->execute([':cid' => $cid]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // === Send Message ===
    if ($_GET['ajax'] === 'send_msg' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $cid = (int)$_POST['client_id'];
        $msg = trim($_POST['msg']);
        if ($cid && $msg !== '') {
            $stmt = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
            $stmt->execute([':id' => $cid]);
            $owner = $stmt->fetchColumn();

            if ($owner === $csr_user) {
                $stmt = $conn->prepare("
                    INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
                    VALUES (:cid, 'csr', :msg, :csr, NOW())
                ");
                $stmt->execute([':cid' => $cid, ':msg' => $msg, ':csr' => $csr_fullname]);
                echo json_encode(['ok' => true]);
            } else {
                echo json_encode(['error' => 'locked']);
            }
        }
        exit;
    }

    if ($_GET['ajax'] === 'typing_status') {
        echo json_encode(['typing' => rand(0, 1)]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=7">
</head>
<body>

<header class="topbar">
  <div class="left">
    <button id="toggleSidebar" class="hamburger">☰</button>
    <img src="AHBALOGO.png" alt="Logo" class="logo">
    <h1>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></h1>
  </div>
  <a href="csr_logout.php" class="logout">Logout</a>
</header>

<!-- Tabs Row -->
<div class="tabs-row">
  <button class="tab-btn active" data-tab="all" onclick="switchTab(this,'all')">💬 All Clients</button>
  <button class="tab-btn" data-tab="mine" onclick="switchTab(this,'mine')">👤 My Clients</button>
  <button class="tab-btn" onclick="window.location.href='survey_responses.php'">📝 Survey Responses</button>
  <button class="tab-btn" onclick="window.location.href='update_profile.php'">👤 Update Profile</button>
</div>

<div class="wrap">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <h3>Navigation</h3>
    <button class="tab active" data-tab="all" onclick="switchTab(this,'all')">💬 All Clients</button>
    <button class="tab" data-tab="mine" onclick="switchTab(this,'mine')">👤 My Clients</button>
    <button class="tab" onclick="window.location.href='survey_responses.php'">📝 Survey Responses</button>
    <button class="tab" onclick="window.location.href='update_profile.php'">👤 Update Profile</button>

    <h3 style="margin-top:20px;">Clients</h3>
    <div id="clientList" class="client-list"></div>
  </aside>

  <!-- Chat Area -->
  <main class="main-area">
    <div class="chat-header">
      <div class="chat-header-left">
        <img id="clientAvatar" class="avatar" src="CSR/lion.PNG" alt="Client Avatar">
        <div>
          <div class="client-name" id="clientName">Select a client</div>
          <div class="client-status" id="clientStatus">Offline</div>
        </div>
      </div>
    </div>

    <div class="messages" id="messages">
      <p class="placeholder">Select a client to start chatting.</p>
    </div>

    <div id="typingIndicator" class="typing" style="display:none;">
      <span></span><span></span><span></span>
    </div>

    <div class="input-area" id="chatInput">
      <input type="text" id="msg" placeholder="Type your message…" onkeyup="typingEvent(event)">
      <button onclick="sendMsg()">Send</button>
    </div>
  </main>
</div>

<script>
let currentClient=null;
let refreshInterval=null;
let canChat=false;

// Sidebar toggle (desktop + mobile)
document.getElementById("toggleSidebar").addEventListener("click",()=>{
  const sidebar=document.getElementById("sidebar");
  sidebar.classList.toggle("collapsed");
});

// === Load Clients ===
function loadClients(tab='all'){
  fetch(`?ajax=load_clients&tab=${tab}`)
  .then(r=>r.json())
  .then(clients=>{
    const list=document.getElementById('clientList');
    list.innerHTML='';
    if(!clients.length){list.innerHTML='<p style="text-align:center;">No clients found.</p>';return;}
    clients.forEach(c=>{
      const isMine=(c.assigned_csr==='<?= $csr_user ?>');
      const locked=(c.assigned_csr && !isMine);
      const avatar=c.name[0].toUpperCase()<='M'?'CSR/lion.PNG':'CSR/penguin.PNG';
      const lockIcon=locked?'🔒':(isMine?'🔓':'');
      const html=`
        <div class="client-item ${locked?'locked':''}" onclick="${locked?'':'selectClient('+c.id+', \''+c.name.replace(/'/g,"\\'")+'\')'}">
          <img src="${avatar}" class="client-avatar">
          <div class="client-meta">
            <div class="client-title">${c.name} <span class="lock-icon">${lockIcon}</span></div>
            <div class="client-sub">${c.status}</div>
          </div>
          ${isMine?`<button onclick="toggleAssign(event,${c.id})">Unassign</button>`:''}
          ${(!c.assigned_csr && !isMine)?`<button onclick="toggleAssign(event,${c.id})">Assign</button>`:''}
        </div>`;
      list.insertAdjacentHTML('beforeend',html);
    });
  });
}

// === Assign / Unassign ===
function toggleAssign(e,id){
  e.stopPropagation();
  fetch(`?ajax=toggle_assign`,{
    method:'POST',
    body:new URLSearchParams({client_id:id})
  }).then(r=>r.json()).then(()=>loadClients());
}

// === Switch Tabs ===
function switchTab(btn,tab){
  document.querySelectorAll('.tab-btn, .tab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  loadClients(tab);
}

// === Select Client ===
function selectClient(id,name){
  currentClient=id;
  document.getElementById('clientName').innerText=name;
  document.getElementById('clientAvatar').src=(name[0].toUpperCase()<='M')?'CSR/lion.PNG':'CSR/penguin.PNG';
  loadChat();
  if(refreshInterval)clearInterval(refreshInterval);
  refreshInterval=setInterval(()=>{loadChat();checkTyping();},3000);
}

// === Load Chat ===
function loadChat(){
  if(!currentClient)return;
  fetch(`?ajax=load_chat&client_id=${currentClient}`)
  .then(r=>r.json())
  .then(data=>{
    const m=document.getElementById('messages');
    m.innerHTML='';
    if(!data.length){m.innerHTML='<p class="placeholder">No messages yet.</p>';return;}
    canChat=(data[0]?.assigned_csr==='<?= $csr_user ?>');
    const input=document.getElementById('chatInput');
    input.classList.toggle('locked',!canChat);
    input.querySelector('input').disabled=!canChat;
    input.querySelector('button').disabled=!canChat;

    data.forEach(msg=>{
      const avatar=(msg.sender_type==='csr')?'<?= $csr_avatar ?>':(msg.client[0].toUpperCase()<='M'?'CSR/lion.PNG':'CSR/penguin.PNG');
      const sender=(msg.sender_type==='csr')?'<?= htmlspecialchars($csr_fullname) ?>':msg.client;
      m.innerHTML+=`
        <div class="message ${msg.sender_type}">
          <div class="bubble"><strong>${sender}:</strong> ${msg.message}<div class="meta">${msg.created_at}</div></div>
        </div>`;
    });
    m.scrollTop=m.scrollHeight;
  });
}

// === Send Message ===
function sendMsg(){
  const msg=document.getElementById('msg').value.trim();
  if(!msg||!currentClient||!canChat)return;
  fetch(`?ajax=send_msg`,{
    method:'POST',
    body:new URLSearchParams({client_id:currentClient,msg})
  }).then(()=>{document.getElementById('msg').value='';loadChat();});
}

function typingEvent(e){if(e.key==='Enter')sendMsg();}
function checkTyping(){
  if(!currentClient)return;
  fetch(`?ajax=typing_status&client_id=${currentClient}`)
  .then(r=>r.json())
  .then(data=>{document.getElementById('typingIndicator').style.display=data.typing?'flex':'none';});
}

loadClients();
</script>
</body>
</html>
