<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];
$csr_name = strtoupper($csr_user);
$logoPath = file_exists("AHBALOGO.png") ? "AHBALOGO.png" : "../SKYTRUFIBER/AHBALOGO.png";

/* -------------------------------
   AJAX HANDLERS
--------------------------------*/
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_GET['ajax'];

    if ($action === 'load_clients') {
        $type = $_GET['type'] ?? 'all';
        if ($type === 'mine') {
            $stmt = $conn->prepare("SELECT id, fullname AS name, last_active 
                                    FROM clients WHERE assigned_csr = :csr 
                                    ORDER BY last_active DESC");
            $stmt->execute([':csr' => $csr_user]);
        } else {
            $stmt = $conn->query("SELECT id, fullname AS name, last_active 
                                  FROM clients ORDER BY last_active DESC");
        }
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($clients as &$c) {
            $c['status'] = (time() - strtotime($c['last_active']) < 300) ? 'Online' : 'Offline';
        }
        echo json_encode($clients);
        exit;
    }

    if ($action === 'load_chat' && isset($_GET['client_id'])) {
        $stmt = $conn->prepare("SELECT message, sender_type, sender_name, created_at
                                FROM chat WHERE client_id = :id ORDER BY created_at ASC");
        $stmt->execute([':id' => $_GET['client_id']]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($action === 'send_msg' && isset($_POST['client_id'], $_POST['message'])) {
        $stmt = $conn->prepare("INSERT INTO chat (client_id, message, sender_type, sender_name, created_at)
                                VALUES (:cid, :msg, 'csr', :csr, NOW())");
        $stmt->execute([
            ':cid' => $_POST['client_id'],
            ':msg' => $_POST['message'],
            ':csr' => $csr_name
        ]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'typing' && isset($_POST['client_id'])) {
        $stmt = $conn->prepare("UPDATE clients SET typing_csr = :csr WHERE id = :id");
        $stmt->execute([':csr' => $csr_user, ':id' => $_POST['client_id']]);
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['error' => 'Invalid action']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_name) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=10">
<style>
body {
  margin: 0;
  font-family: 'Segoe UI', Arial, sans-serif;
  background: #eefcf4;
  color: #042;
}

/* HEADER */
header {
  background: #007743;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
}
header img { height: 40px; border-radius: 8px; }
.logout { color: white; text-decoration: none; font-weight: bold; }
.hamburger {
  background: none;
  border: none;
  font-size: 24px;
  color: white;
  cursor: pointer;
  margin-right: 10px;
}

/* CONTAINER */
.container {
  display: grid;
  grid-template-columns: 280px 1fr;
  height: calc(100vh - 60px);
  transition: grid-template-columns 0.3s;
}
.container.collapsed {
  grid-template-columns: 0 1fr;
}

/* SIDEBAR */
.sidebar {
  background: #e9fdf0;
  padding: 10px;
  overflow-y: auto;
  border-right: 1px solid #cde5d4;
  transition: transform 0.3s ease;
}
.container.collapsed .sidebar {
  transform: translateX(-100%);
}

/* TABS */
.tabs {
  display: flex;
  background: #e6f9ee;
  border-bottom: 1px solid #c5e7d1;
}
.tabs button {
  background: transparent;
  border: none;
  padding: 10px 20px;
  font-weight: bold;
  cursor: pointer;
  color: #046b3a;
}
.tabs button.active {
  background: #0aa05b;
  color: white;
  border-radius: 10px 10px 0 0;
}

/* CLIENTS */
.client-item {
  background: white;
  padding: 10px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  cursor: pointer;
  gap: 8px;
  border: 1px solid #d9e8dd;
}
.client-item:hover { background: #f4fff9; }
.client-avatar { width: 30px; height: 30px; border-radius: 50%; }

/* CHAT */
.chat-area {
  display: flex;
  flex-direction: column;
  background: white;
}
.chat-header {
  background: #007743;
  color: white;
  padding: 10px 16px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.chat-header img { width: 40px; height: 40px; border-radius: 50%; }
.chat-body {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
  background: #f9fffb;
}
.chat-input {
  display: flex;
  padding: 10px;
  border-top: 1px solid #cde5d4;
}
.chat-input input {
  flex: 1;
  border: 1px solid #cde5d4;
  border-radius: 8px;
  padding: 10px;
}
.chat-input button {
  margin-left: 8px;
  border: none;
  background: #0aa05b;
  color: white;
  font-weight: bold;
  border-radius: 8px;
  padding: 10px 16px;
}

/* MESSAGES */
.message { margin: 6px 0; max-width: 70%; }
.message.csr .bubble { background: #e7f3ff; margin-left: auto; }
.message.client .bubble { background: #eaffef; margin-right: auto; }
.bubble { border-radius: 10px; padding: 10px 12px; }
.meta { font-size: 11px; color: #666; margin-top: 4px; }

.typing { display: flex; justify-content: center; height: 20px; }
.typing span {
  width: 6px; height: 6px; background: #999; border-radius: 50%;
  margin: 0 2px; animation: blink 1.2s infinite ease-in-out;
}
.typing span:nth-child(2){animation-delay:0.2s;}
.typing span:nth-child(3){animation-delay:0.4s;}
@keyframes blink { 0%,80%,100%{opacity:0.3;} 40%{opacity:1;} }
</style>
</head>
<body>

<header>
  <div style="display:flex;align-items:center;gap:10px;">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <img src="<?= $logoPath ?>" alt="Logo">
    <strong>CSR Dashboard — <?= htmlspecialchars($csr_name) ?></strong>
  </div>
  <a href="csr_logout.php" class="logout">Logout</a>
</header>

<div class="tabs">
  <button class="active" id="btnAll" onclick="loadClients('all')">💬 All Clients</button>
  <button id="btnMine" onclick="loadClients('mine')">👤 My Clients</button>
  <button onclick="window.location.href='survey_responses.php'">📝 Survey Responses</button>
  <button onclick="window.location.href='update_profile.php'">👤 Update Profile</button>
</div>

<div class="container" id="mainContainer">
  <aside class="sidebar">
    <div id="clientList"></div>
  </aside>

  <main class="chat-area">
    <div class="chat-header">
      <img src="CSR/lion.PNG" id="clientAvatar" alt="">
      <div>
        <strong id="clientName">Select a client</strong><br>
        <small id="clientStatus">Offline</small>
      </div>
    </div>
    <div class="chat-body" id="chatMessages">
      <p style="text-align:center;color:#888;">Select a client to start chatting.</p>
    </div>
    <div id="typingIndicator" class="typing" style="display:none;">
      <span></span><span></span><span></span>
    </div>
    <div class="chat-input">
      <input type="text" id="msgInput" placeholder="Type your message…" onkeyup="handleTyping(event)">
      <button onclick="sendMessage()">Send</button>
    </div>
  </main>
</div>

<script>
let currentClient = null;
let refreshInterval = null;

// SIDEBAR TOGGLE
function toggleSidebar() {
  document.getElementById('mainContainer').classList.toggle('collapsed');
}

// LOAD CLIENTS
function loadClients(type='all') {
  document.getElementById('btnAll').classList.toggle('active', type==='all');
  document.getElementById('btnMine').classList.toggle('active', type==='mine');
  fetch(`csr_dashboard.php?ajax=load_clients&type=${type}`)
    .then(res => res.json())
    .then(data => {
      const list = document.getElementById('clientList');
      list.innerHTML = '';
      if (!data || data.length === 0) {
        list.innerHTML = '<p style="text-align:center;color:#666;">No clients found.</p>';
        return;
      }
      data.forEach(client => {
        const div = document.createElement('div');
        div.className = 'client-item';
        div.innerHTML = `
          <img src="${client.name[0].toUpperCase()<='M'?'CSR/lion.PNG':'CSR/penguin.PNG'}" class="client-avatar">
          <div><strong>${client.name}</strong><br><small>${client.status}</small></div>`;
        div.onclick = () => selectClient(client.id, client.name, client.status);
        list.appendChild(div);
      });
    });
}

// SELECT CLIENT
function selectClient(id,name,status) {
  currentClient = id;
  document.getElementById('clientName').innerText = name;
  document.getElementById('clientStatus').innerText = status;
  document.getElementById('clientAvatar').src = (name[0].toUpperCase()<='M')?'CSR/lion.PNG':'CSR/penguin.PNG';
  loadChat();
  if (refreshInterval) clearInterval(refreshInterval);
  refreshInterval = setInterval(loadChat, 3000);
}

// LOAD CHAT
function loadChat() {
  if (!currentClient) return;
  fetch(`csr_dashboard.php?ajax=load_chat&client_id=${currentClient}`)
    .then(res => res.json())
    .then(data => {
      const chat = document.getElementById('chatMessages');
      chat.innerHTML = '';
      if (!data || data.length === 0) {
        chat.innerHTML = '<p style="text-align:center;color:#888;">No messages yet.</p>';
        return;
      }
      data.forEach(m => {
        const div = document.createElement('div');
        div.className = `message ${m.sender_type}`;
        div.innerHTML = `
          <div class="bubble">
            <strong>${m.sender_name}:</strong> ${m.message}
            <div class="meta">${m.created_at}</div>
          </div>`;
        chat.appendChild(div);
      });
      chat.scrollTop = chat.scrollHeight;
    });
}

// SEND MESSAGE
function sendMessage() {
  const msg = document.getElementById('msgInput').value.trim();
  if (!msg || !currentClient) return;
  fetch('csr_dashboard.php?ajax=send_msg', {
    method: 'POST',
    body: new URLSearchParams({ client_id: currentClient, message: msg })
  })
  .then(() => {
    document.getElementById('msgInput').value = '';
    loadChat();
  });
}

// TYPING INDICATOR
let typingTimeout;
function handleTyping(e) {
  if (e.key === 'Enter') sendMessage();
  if (!currentClient) return;
  document.getElementById('typingIndicator').style.display = 'flex';
  clearTimeout(typingTimeout);
  typingTimeout = setTimeout(()=>document.getElementById('typingIndicator').style.display='none',1500);
  fetch('csr_dashboard.php?ajax=typing', {
    method:'POST',
    body:new URLSearchParams({client_id:currentClient})
  });
}

document.addEventListener("DOMContentLoaded", () => loadClients('all'));
</script>
</body>
</html>
