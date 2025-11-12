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
<title>CSR Dashboard — <?= htmlspecialchars($csr_user) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=5">
<style>
:root {
  --green:#0aa05b;
  --green-dark:#056b3d;
  --light:#eefcf4;
  --soft:#f6fff9;
  --line:#e2ece5;
}
body {
  margin:0;
  font-family:'Segoe UI',Arial,sans-serif;
  background:var(--light);
  color:#042;
}
header {
  background:var(--green);
  color:#fff;
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:10px 16px;
}
.header-left { display:flex; align-items:center; gap:10px; }
.logo { height:40px; border-radius:6px; }
.logout { color:#fff; text-decoration:none; font-weight:bold; }
.container {
  display:grid;
  grid-template-columns:280px 1fr;
  height:calc(100vh - 60px);
}
.sidebar {
  background:#e9fdf0;
  padding:10px;
  overflow-y:auto;
  border-right:1px solid var(--line);
}
.sidebar-tabs { margin-bottom: 10px; }
.sidebar .tab {
  display:block;
  width:100%;
  padding:10px 12px;
  border:none;
  border-radius:10px;
  margin-bottom:8px;
  background:white;
  cursor:pointer;
  font-weight:bold;
  color:var(--green-dark);
}
.sidebar .tab.active {
  background:var(--green);
  color:white;
}
.client-list {
  overflow-y:auto;
  max-height:calc(100vh - 240px);
}
.client-item {
  display:flex;
  align-items:center;
  gap:8px;
  background:white;
  border:1px solid var(--line);
  border-radius:10px;
  padding:8px;
  margin-bottom:8px;
  cursor:pointer;
}
.client-item:hover { background:#f7fff9; }
.client-avatar {
  width:32px; height:32px;
  border-radius:50%;
}
.chat-area {
  display:flex;
  flex-direction:column;
  background:white;
}
.chat-header {
  display:flex;
  align-items:center;
  gap:12px;
  background:var(--green-dark);
  color:white;
  padding:10px 16px;
}
.chat-header .avatar {
  width:40px; height:40px;
  border-radius:50%;
  border:2px solid white;
}
.chat-info h2 { margin:0; font-size:18px; }
.chat-info p { margin:0; font-size:13px; }
.chat-body {
  flex:1;
  padding:16px;
  overflow-y:auto;
  background:var(--soft);
}
.placeholder {
  color:#888;
  text-align:center;
  margin-top:40px;
}
.message {
  margin:8px 0;
  max-width:70%;
}
.message.client .bubble {
  background:#eaffef;
  margin-right:auto;
}
.message.csr .bubble {
  background:#e6f3ff;
  margin-left:auto;
}
.bubble {
  border-radius:10px;
  padding:10px 12px;
  box-shadow:0 1px 3px rgba(0,0,0,0.1);
}
.meta {
  font-size:11px;
  color:#666;
  margin-top:4px;
}
.chat-input {
  display:flex;
  padding:10px;
  border-top:1px solid var(--line);
  background:white;
}
.chat-input input {
  flex:1;
  padding:10px;
  border:1px solid var(--line);
  border-radius:8px;
}
.chat-input button {
  margin-left:8px;
  background:var(--green);
  color:white;
  border:none;
  border-radius:8px;
  padding:10px 16px;
  font-weight:bold;
}
.typing {
  display:flex;
  justify-content:center;
  align-items:center;
  height:20px;
}
.typing span {
  width:6px;
  height:6px;
  background:#999;
  border-radius:50%;
  margin:0 2px;
  animation:blink 1.2s infinite ease-in-out;
}
.typing span:nth-child(2){animation-delay:0.2s;}
.typing span:nth-child(3){animation-delay:0.4s;}
@keyframes blink { 0%,80%,100%{opacity:0.3;} 40%{opacity:1;} }
</style>
</head>
<body>

<header>
    <div class="header-left">
        <img src="<?= $logoPath ?>" alt="Logo" class="logo">
        <h1>CSR Dashboard — <?= htmlspecialchars($csr_name) ?></h1>
    </div>
    <a href="csr_logout.php" class="logout">Logout</a>
</header>

<div class="container">
    <aside class="sidebar">
        <div class="sidebar-tabs">
            <button class="tab active" id="btnAll" onclick="loadClients('all')">💬 All Clients</button>
            <button class="tab" id="btnMine" onclick="loadClients('mine')">👤 My Clients</button>
            <button class="tab" onclick="window.location.href='survey_responses.php'">📝 Survey Responses</button>
            <button class="tab" onclick="window.location.href='update_profile.php'">👤 Update Profile</button>
        </div>
        <div id="clientList" class="client-list"></div>
    </aside>

    <main class="chat-area">
        <div class="chat-header">
            <img src="CSR/lion.PNG" id="clientAvatar" class="avatar" alt="">
            <div class="chat-info">
                <h2 id="clientName">Select a client</h2>
                <p id="clientStatus">Offline</p>
            </div>
        </div>

        <div class="chat-body" id="chatMessages">
            <p class="placeholder">Select a client to start chatting.</p>
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

function loadClients(type='all') {
    document.getElementById('btnAll').classList.toggle('active', type==='all');
    document.getElementById('btnMine').classList.toggle('active', type==='mine');

    fetch(`csr_dashboard.php?ajax=load_clients&type=${type}`)
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('clientList');
            list.innerHTML = '';
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

function selectClient(id,name,status) {
    currentClient = id;
    document.getElementById('clientName').innerText = name;
    document.getElementById('clientStatus').innerText = status;
    document.getElementById('clientAvatar').src = (name[0].toUpperCase()<='M')?'CSR/lion.PNG':'CSR/penguin.PNG';
    loadChat();
    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(loadChat, 3000);
}

function loadChat() {
    if (!currentClient) return;
    fetch(`csr_dashboard.php?ajax=load_chat&client_id=${currentClient}`)
        .then(res => res.json())
        .then(data => {
            const chat = document.getElementById('chatMessages');
            chat.innerHTML = '';
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

loadClients();
</script>
</body>
</html>
