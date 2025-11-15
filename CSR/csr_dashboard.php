<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

$stmt = $conn->prepare("SELECT csr_fullname, profile_pic FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$csr_fullname = $data['csr_fullname'] ?? $csr_user;
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
<link rel="stylesheet" href="csr_dashboard.css?v=11">
</head>
<body>

<header class="topbar">
  <div class="top-left">
    <button id="openSidebar">☰</button>
    <img src="AHBALOGO.png" class="logo" alt="Logo">
    <h2>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></h2>
  </div>
  <a href="csr_logout.php" class="logout">Logout</a>
</header>

<aside id="sidebar" class="sidebar">
  <div class="sidebar-header">
    <span>Menu</span>
    <button id="closeSidebar">✖</button>
  </div>
  <button class="tab active" onclick="switchTab(this,'all')">💬 Chat Dashboard</button>
  <button class="tab" onclick="switchTab(this,'mine')">👤 My Clients</button>
  <button class="tab" onclick="window.location.href='survey_responses.php'">📝 Survey Responses</button>
  <button class="tab" onclick="window.location.href='update_profile.php'">👤 Edit Profile</button>
</aside>

<div class="content">

<section class="client-panel">
  <h3>Clients</h3>
  <div id="clientList" class="client-list"></div>
</section>

<main class="chat-panel">
  <div class="chat-header">
    <div class="chat-header-left">
      <img id="chatAvatar" src="CSR/lion.PNG" class="chat-avatar" alt="Client Avatar">
      <div>
        <div class="chat-name" id="chatName">Select a client</div>
        <div class="chat-status" id="chatStatus">—</div>
      </div>
    </div>
  </div>

  <div id="chatBox" class="chat-box">
    <p class="placeholder">Select a client to start chatting.</p>
  </div>

  <div id="uploadPreview" class="upload-preview" style="display:none;"></div>

  <div id="chatInput" class="chat-input disabled">
    <label for="fileUpload" class="upload-btn">📎</label>
    <input type="file" id="fileUpload" style="display:none">
    <input type="text" id="msg" placeholder="Type your message..." disabled>
    <button id="sendBtn" disabled>Send</button>
  </div>
</main>

</div>

<script>
let currentClient  = null;
let canChat        = false;
let selectedFile   = null;
let csr_user       = "<?= $csr_user ?>";
let csr_fullname   = "<?= htmlspecialchars($csr_fullname, ENT_QUOTES) ?>";
let refreshTimer   = null;

document.getElementById('openSidebar').onclick  = () => document.getElementById('sidebar').classList.add('active');
document.getElementById('closeSidebar').onclick = () => document.getElementById('sidebar').classList.remove('active');

function loadClients(tab='all'){
  fetch(`/CSR/csr_dashboard.php?ajax=load_clients&tab=${tab}`)
    .then(r=>r.json())
    .then(clients=>{
      const list = document.getElementById('clientList');
      list.innerHTML = '';

      if(!clients.length){
        list.innerHTML = '<p class="empty">No clients found.</p>';
        return;
      }

      clients.forEach(c=>{
        const avatar = (c.name && c.name[0].toUpperCase() <= 'M') ? 'CSR/lion.PNG' : 'CSR/penguin.PNG';

        let actionBtn = '';
        if (!c.assigned_csr) {
          actionBtn = `<button class="pill green" onclick="event.stopPropagation();assignClient(${c.id});">＋</button>`;
        } else if (c.assigned_csr === csr_user) {
          actionBtn = `<button class="pill red" onclick="event.stopPropagation();unassignClient(${c.id});">−</button>`;
        } else {
          actionBtn = `<button class="pill gray" disabled>🔒</button>`;
        }

        const lockedClass = (c.assigned_csr && c.assigned_csr !== csr_user) ? 'locked' : '';

        list.insertAdjacentHTML('beforeend', `
          <div class="client-item ${lockedClass}" onclick="openClient(${c.id}, '${(c.name||'').replace(/'/g,"\\'")}')">
            <div class="client-main">
              <img src="${avatar}" class="client-avatar">
              <div class="client-meta">
                <div class="client-name">${c.name || ''}</div>
                <div class="client-sub">
                  <span class="${c.status === 'Online' ? 'online-dot':'offline-dot'}"></span>
                  ${c.status || 'Offline'}
                  ${c.assigned_csr ? `• CSR: ${c.assigned_csr}` : '• Unassigned'}
                </div>
              </div>
            </div>
            <div class="client-actions">${actionBtn}</div>
          </div>
        `);
      });
    });
}

function switchTab(btn, tab){
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  loadClients(tab);
}

function assignClient(id){
  fetch(`/CSR/csr_dashboard.php?ajax=assign&id=${id}`)
    .then(r=>r.json())
    .then(()=>loadClients());
}

function unassignClient(id){
  if(!confirm('Unassign this client from you?')) return;
  fetch(`/CSR/csr_dashboard.php?ajax=unassign&id=${id}`)
    .then(r=>r.json())
    .then(()=>loadClients());
}

function openClient(id, name){
  currentClient = id;
  document.getElementById('chatName').innerText = name || 'Unknown client';

  const avatar = (name && name[0].toUpperCase() <= 'M') ? 'CSR/lion.PNG' : 'CSR/penguin.PNG';
  document.getElementById('chatAvatar').src = avatar;

  fetch(`/CSR/csr_dashboard.php?ajax=get_client&id=${id}`)
    .then(r=>r.json())
    .then(c=>{
      const assigned = c.assigned_csr;
      canChat = (!assigned || assigned === csr_user);

      const statusEl = document.getElementById('chatStatus');
      if (!assigned) {
        statusEl.innerText = 'Unassigned — you can claim this client.';
      } else if (assigned === csr_user) {
        statusEl.innerText = 'Assigned to you';
      } else {
        statusEl.innerText = `Assigned to CSR: ${assigned}`;
      }

      const input = document.getElementById('chatInput');
      const msg   = document.getElementById('msg');
      const btn   = document.getElementById('sendBtn');

      input.classList.toggle('disabled', !canChat);
      msg.disabled  = !canChat;
      btn.disabled  = !canChat;

      loadChat();
      if (refreshTimer) clearInterval(refreshTimer);
      refreshTimer = setInterval(loadChat, 3000);
    });
}

function loadChat(){
  if (!currentClient) return;
  fetch(`/SKYTRUFIBER/load_chat.php?client_id=${currentClient}&viewer=csr`)
    .then(r=>r.json())
    .then(rows=>{
      const box = document.getElementById('chatBox');
      box.innerHTML = '';

      if (!rows.length) {
        box.innerHTML = '<p class="placeholder">No messages yet.</p>';
        return;
      }

      rows.forEach(m=>{
        let fileItem = '';
        if (m.file_path) {
          if (/\.(jpg|jpeg|png|gif)$/i.test(m.file_path)) {
            fileItem = `<div class="file-wrap"><img src="${m.file_path}" class="file-img"></div>`;
          } else {
            fileItem = `<div class="file-wrap"><a href="${m.file_path}" download>📎 ${m.file_name || 'Download file'}</a></div>`;
          }
        }

        let meta = m.created_at;
        if (m.sender_type === 'csr') {
          const seen = (m.is_seen == 1);
          meta = `${seen ? '✔✔' : '✔'} ${meta}`;
        }

        box.insertAdjacentHTML('beforeend', `
          <div class="msg ${m.sender_type}">
            <div class="bubble">
              ${m.message ? m.message : ''} 
              ${fileItem}
              <div class="meta">${meta}</div>
            </div>
          </div>
        `);
      });

      box.scrollTop = box.scrollHeight;
    });
}

document.getElementById('fileUpload').addEventListener('change', function(){
  const file = this.files[0];
  if (!file) {
    selectedFile = null;
    document.getElementById('uploadPreview').style.display = 'none';
    return;
  }
  selectedFile = file;
  const preview = document.getElementById('uploadPreview');
  preview.style.display = 'block';
  preview.innerText = 'Attached: ' + file.name;
});

document.getElementById('sendBtn').addEventListener('click', sendMsg);
document.getElementById('msg').addEventListener('keyup', e=>{
  if (e.key === 'Enter') sendMsg();
});

function sendMsg(){
  if (!currentClient || !canChat) {
    alert("You can't reply to a client not assigned to you.");
    return;
  }
  const text = document.getElementById('msg').value.trim();
  if (!text && !selectedFile) return;

  const fd = new FormData();
  fd.append('sender_type', 'csr');
  fd.append('message', text);
  fd.append('client_id', currentClient);
  fd.append('csr_user', csr_user);
  fd.append('csr_fullname', csr_fullname);
  if (selectedFile) {
    fd.append('file', selectedFile);
  }

  fetch(`/SKYTRUFIBER/save_chat.php`, { method: 'POST', body: fd })
    .then(r=>r.json())
    .then(res=>{
      if (res.status === 'ok') {
        document.getElementById('msg').value = '';
        selectedFile = null;
        document.getElementById('fileUpload').value = '';
        document.getElementById('uploadPreview').style.display = 'none';
        loadChat();
      } else {
        alert(res.msg || 'Send failed');
      }
    });
}

loadClients();
</script>
</body>
</html>
