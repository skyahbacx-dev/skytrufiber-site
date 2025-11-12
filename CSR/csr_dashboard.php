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
   AJAX HANDLERS (merged)
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
<link rel="stylesheet" href="csr_dashboard.css?v=4">
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
    <aside class="sidebar" id="clientList">
        <button class="tab active" onclick="loadClients('all')">💬 All Clients</button>
        <button class="tab" onclick="loadClients('mine')">👤 My Clients</button>
        <button class="tab" onclick="window.location.href='survey_responses.php'">📝 Survey Responses</button>
        <button class="tab" onclick="window.location.href='update_profile.php'">👤 Update Profile</button>
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
// === GLOBAL VARS ===
let currentClient = null;
let refreshInterval = null;

// === LOAD CLIENTS ===
function loadClients(type='all') {
    fetch(`csr_dashboard.php?ajax=load_clients&type=${type}`)
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('clientList');
            list.innerHTML = `
                <button class="tab ${type==='all'?'active':''}" onclick="loadClients('all')">💬 All Clients</button>
                <button class="tab ${type==='mine'?'active':''}" onclick="loadClients('mine')">👤 My Clients</button>
                <button class="tab" onclick="window.location.href='survey_responses.php'">📝 Survey Responses</button>
                <button class="tab" onclick="window.location.href='update_profile.php'">👤 Update Profile</button>
            `;
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

// === SELECT CLIENT ===
function selectClient(id,name,status) {
    currentClient = id;
    document.getElementById('clientName').innerText = name;
    document.getElementById('clientStatus').innerText = status;
    document.getElementById('clientAvatar').src = (name[0].toUpperCase()<='M')?'CSR/lion.PNG':'CSR/penguin.PNG';
    loadChat();
    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(loadChat, 3000);
}

// === LOAD CHAT ===
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

// === SEND MESSAGE ===
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

// === TYPING INDICATOR ===
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

// Init
loadClients();
</script>
</body>
</html>
