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

    // === Get Client Info ===
    if ($_GET['ajax'] === 'get_client_info' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT name, district, barangay, email, date_installed, balance, assigned_csr 
                                FROM clients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit;
    }

    // === Assign Client ===
    if ($_GET['ajax'] === 'assign_client' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id AND assigned_csr IS NULL");
        $stmt->execute([':id' => $id, ':csr' => $csr_user]);
        echo json_encode(['success' => true]);
        exit;
    }

    // === Unassign Client ===
    if ($_GET['ajax'] === 'unassign_client' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :id AND assigned_csr = :csr");
        $stmt->execute([':id' => $id, ':csr' => $csr_user]);
        echo json_encode(['success' => true]);
        exit;
    }

    // === Load Chat ===
    if ($_GET['ajax'] === 'load_chat' && isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];
        $stmt = $conn->prepare("
            SELECT c.name AS client, ch.* 
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
            $stmt = $conn->prepare("
                INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
                VALUES (:cid, 'csr', :msg, :csr, NOW())
            ");
            $stmt->execute([':cid' => $cid, ':msg' => $msg, ':csr' => $csr_fullname]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=6">
</head>
<body>

<header class="topbar">
  <div class="left">
    <img src="AHBALOGO.png" alt="Logo" class="logo">
    <h1>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></h1>
  </div>
  <a href="csr_logout.php" class="logout">Logout</a>
</header>

<div class="wrap">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <h3>Menu</h3>
      <button class="hamburger" onclick="toggleSidebar()">☰</button>
    </div>

    <button class="tab active" data-tab="all" onclick="switchTab(this, 'all')">💬 All Clients</button>
    <button class="tab" data-tab="mine" onclick="switchTab(this, 'mine')">👤 My Clients</button>
    <button class="tab" onclick="window.location.href='survey_responses.php'">📝 Survey Responses</button>
    <button class="tab" onclick="window.location.href='update_profile.php'">👤 Update Profile</button>

    <h3 style="margin-top:20px;">Clients</h3>
    <div id="clientList" class="client-list"></div>
  </aside>

  <!-- Main Chat Area -->
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

    <div class="input-area" id="chatInput">
      <input type="text" id="msg" placeholder="Type your message…" onkeyup="typingEvent(event)">
      <button onclick="sendMsg()">Send</button>
    </div>
  </main>
</div>

<script>
let currentClient = null;
let refreshInterval = null;
let canChat = false;

// === Sidebar Toggle ===
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('collapsed');
}

// === Load Clients ===
async function loadClients(tab = 'all') {
  const res = await fetch(`?ajax=load_clients&tab=${tab}`);
  const clients = await res.json();
  const list = document.getElementById('clientList');
  list.innerHTML = '';

  if (!clients.length) {
    list.innerHTML = `<p style="text-align:center;color:#666;">No clients found.</p>`;
    return;
  }

  clients.forEach(c => {
    const avatar = c.name[0].toUpperCase() <= 'M' ? 'CSR/lion.PNG' : 'CSR/penguin.PNG';
    const isLocked = c.assigned_csr && c.assigned_csr !== '<?= $csr_user ?>';
    const lockIcon = isLocked ? '🔒' : '';
    const statusTag = !c.assigned_csr ? '<span class="unassigned-tag">🟢 Unassigned</span>' : '';

    const html = `
      <div class="client-item ${isLocked ? 'locked' : ''}" onclick="toggleClientInfo(this, ${c.id}, '${c.name.replace(/'/g, "\\'")}')">
        <img src="${avatar}" class="client-avatar">
        <div class="client-meta">
          <div class="client-title">${c.name} ${lockIcon}</div>
          <div class="client-sub">${c.status} ${statusTag}</div>
        </div>
        <div class="client-info" id="client-info-${c.id}" style="display:none;">
          <p>Loading info...</p>
        </div>
      </div>`;
    list.insertAdjacentHTML('beforeend', html);
  });
}

// === Switch Tab ===
function switchTab(btn, tab) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  loadClients(tab);
}

// === Expand Client Info ===
async function toggleClientInfo(el, clientId, name) {
  const infoDiv = document.getElementById(`client-info-${clientId}`);
  const isVisible = infoDiv.style.display === 'block';

  if (isVisible) {
    infoDiv.style.display = 'none';
    return;
  }

  document.querySelectorAll('.client-info').forEach(div => div.style.display = 'none');

  const res = await fetch(`?ajax=get_client_info&id=${clientId}`);
  const data = await res.json();

  const isOwner = data.assigned_csr === '<?= $csr_user ?>';
  const unassigned = !data.assigned_csr;
  const lockMsg = !isOwner && data.assigned_csr ? `<p style="color:#b00;">🔒 This client belongs to ${data.assigned_csr}</p>` : '';

  infoDiv.innerHTML = `
    <div class="info-details">
      ${lockMsg}
      <strong>District:</strong> ${data.district ?? '-'}<br>
      <strong>Barangay:</strong> ${data.barangay ?? '-'}<br>
      <strong>Email:</strong> ${data.email ?? '-'}<br>
      <strong>Date Installed:</strong> ${data.date_installed ?? '-'}<br>
      <strong>Balance:</strong> ₱${data.balance ?? '0.00'}<br>
      <strong>Assigned CSR:</strong> ${data.assigned_csr ?? '🟢 None'}<br>
      ${isOwner ? `
        <button class="assign-btn" onclick="unassignClient(${clientId}, event)">Unassign</button>
      ` : unassigned ? `
        <button class="assign-btn assign-now" onclick="assignClient(${clientId}, event)">Assign to Me</button>
      ` : ''}
      <button class="assign-btn" style="margin-top:8px;background:#0a7f46;" onclick="selectClient(${clientId}, '${name.replace(/'/g, "\\'")}', ${isOwner})">Open Chat</button>
    </div>
  `;
  infoDiv.style.display = 'block';
}

// === Assign / Unassign ===
async function unassignClient(id, e) {
  e.stopPropagation();
  const res = await fetch(`?ajax=unassign_client&id=${id}`);
  const data = await res.json();
  if (data.success) loadClients();
}

async function assignClient(id, e) {
  e.stopPropagation();
  const res = await fetch(`?ajax=assign_client&id=${id}`);
  const data = await res.json();
  if (data.success) loadClients();
}

// === Chat ===
function selectClient(id, name, isOwner) {
  currentClient = id;
  canChat = isOwner;
  document.getElementById('clientName').innerText = name;
  document.getElementById('chatInput').classList.toggle('locked', !isOwner);
  loadChat();

  if (refreshInterval) clearInterval(refreshInterval);
  refreshInterval = setInterval(loadChat, 3000);
}

async function loadChat() {
  if (!currentClient) return;
  const res = await fetch(`?ajax=load_chat&client_id=${currentClient}`);
  const data = await res.json();
  const m = document.getElementById('messages');
  m.innerHTML = '';

  if (!data.length) {
    m.innerHTML = '<p class="placeholder">No messages yet.</p>';
    return;
  }

  data.forEach(msg => {
    const avatar = (msg.sender_type === 'csr')
      ? '<?= $csr_avatar ?>'
      : 'CSR/lion.PNG';
    const sender = (msg.sender_type === 'csr')
      ? '<?= htmlspecialchars($csr_fullname) ?>'
      : msg.client;

    m.innerHTML += `
      <div class="message ${msg.sender_type}">
        <div class="bubble">
          <strong>${sender}:</strong> ${msg.message}
          <div class="meta">${msg.created_at}</div>
        </div>
      </div>`;
  });
  m.scrollTop = m.scrollHeight;
}

// === Send Msg ===
async function sendMsg() {
  if (!canChat) return alert("You can't chat with clients not assigned to you.");
  const msg = document.getElementById('msg').value.trim();
  if (!msg || !currentClient) return;
  await fetch('?ajax=send_msg', {
    method: 'POST',
    body: new URLSearchParams({ client_id: currentClient, msg })
  });
  document.getElementById('msg').value = '';
  loadChat();
}

function typingEvent(e) {
  if (e.key === 'Enter') sendMsg();
}

// Init
loadClients();
</script>
</body>
</html>
