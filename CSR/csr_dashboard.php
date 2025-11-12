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

    /* Load Clients */
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

    /* Load Chat */
    if ($_GET['ajax'] === 'load_chat') {
        $cid = (int)$_GET['client_id'];
        $stmt = $conn->prepare("SELECT c.full_name AS client, ch.* FROM chat ch JOIN clients c ON ch.client_id = c.id WHERE ch.client_id = :cid ORDER BY ch.created_at ASC");
        $stmt->execute([':cid' => $cid]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($messages);
        exit;
    }

    /* Send Message */
    if ($_GET['ajax'] === 'send_msg') {
        $cid = (int)$_POST['client_id'];
        $msg = trim($_POST['msg']);
        if ($msg) {
            $stmt = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, csr_fullname) VALUES (:cid, 'csr', :msg, :csr)");
            $stmt->execute([':cid' => $cid, ':msg' => $msg, ':csr' => $csr_fullname]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    /* Typing Status */
    if ($_GET['ajax'] === 'typing_status') {
        echo json_encode(['typing' => rand(0, 1)]); // Simulated typing
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
<link rel="stylesheet" href="csr_dashboard.css">
</head>
<body>

<header>
    <div class="logo">
        <img src="AHBALOGO.png" alt="Logo">
        <h1>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></h1>
    </div>
    <a href="csr_logout.php" class="logout-btn">Logout</a>
</header>

<div class="container">
    <aside class="sidebar">
        <button class="tab active" onclick="switchTab('all')">💬 All Clients</button>
        <button class="tab" onclick="switchTab('mine')">👤 My Clients</button>
        <button class="tab" onclick="switchTab('rem')">⏰ Reminders</button>
        <button class="tab" onclick="window.location.href='survey_responses.php'">📝 Survey Responses</button>
        <button class="tab" onclick="window.location.href='update_profile.php'">👤 Update Profile</button>
    </aside>

    <section class="clients" id="clientList"></section>

    <section class="chat">
        <div class="chat-header">
            <img id="clientAvatar" class="avatar" src="CSR/lion.PNG" alt="Client Avatar">
            <div>
                <h2 id="clientName">Select a client</h2>
                <span id="clientStatus">Offline</span>
            </div>
        </div>

        <div class="messages" id="messages"></div>

        <div id="typingIndicator" class="typing" style="display:none;">
            <span></span><span></span><span></span>
        </div>

        <div class="chat-input">
            <input type="text" id="msg" placeholder="Type a reply…" onkeyup="typingEvent(event)">
            <button onclick="sendMsg()">Send</button>
        </div>
    </section>
</div>

<script>
let currentClient = null;
let refreshInterval;

// === Load Clients ===
function loadClients(tab='all') {
    fetch(`?ajax=load_clients&tab=${tab}`)
    .then(res => res.json())
    .then(clients => {
        let html = '';
        clients.forEach(c => {
            let avatar = c.name[0].toUpperCase() <= 'M' ? 'CSR/lion.PNG' : 'CSR/penguin.PNG';
            html += `
                <div class="client-item" onclick="selectClient(${c.id}, '${c.name}')">
                    <div>
                        <strong>${c.name}</strong><br>
                        <small>${c.status}</small>
                    </div>
                    <img src="${avatar}" class="msg-avatar">
                </div>`;
        });
        document.getElementById('clientList').innerHTML = html;
    });
}

function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
    loadClients(tab);
}

// === Select Client ===
function selectClient(id, name) {
    currentClient = id;
    document.getElementById('clientName').innerText = name;
    document.getElementById('clientAvatar').src = (name[0].toUpperCase() <= 'M') ? 'CSR/lion.PNG' : 'CSR/penguin.PNG';
    loadChat();
    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(() => { loadChat(); checkTyping(); }, 3000);
}

// === Load Chat ===
function loadChat() {
    if (!currentClient) return;
    fetch(`?ajax=load_chat&client_id=${currentClient}`)
    .then(res => res.json())
    .then(data => {
        let html = '';
        data.forEach(msg => {
            let avatar = (msg.sender_type === 'csr') ? '<?= $csr_avatar ?>' : (msg.client[0].toUpperCase() <= 'M' ? 'CSR/lion.PNG' : 'CSR/penguin.PNG');
            let sender = (msg.sender_type === 'csr') ? '<?= htmlspecialchars($csr_fullname) ?>' : msg.client;
            html += `
                <div class="msg ${msg.sender_type}">
                    <img src="${avatar}" class="msg-avatar">
                    <div class="msg-bubble">
                        <strong>${sender}:</strong> ${msg.message}
                        <div class="msg-time">${msg.created_at}</div>
                    </div>
                </div>`;
        });
        const m = document.getElementById('messages');
        m.innerHTML = html;
        m.scrollTop = m.scrollHeight;
    });
}

// === Send Message ===
function sendMsg() {
    const msg = document.getElementById('msg').value.trim();
    if (!msg || !currentClient) return;
    fetch(`?ajax=send_msg`, {
        method: 'POST',
        body: new URLSearchParams({ client_id: currentClient, msg })
    }).then(() => {
        document.getElementById('msg').value = '';
        loadChat();
    });
}

// === Typing Event ===
function typingEvent(e) {
    if (e.key === 'Enter') sendMsg();
}

// === Check Typing ===
function checkTyping() {
    fetch(`?ajax=typing_status&client_id=${currentClient || 0}`)
    .then(res => res.json())
    .then(data => {
        document.getElementById('typingIndicator').style.display = data.typing ? 'flex' : 'none';
    });
}

// === Initialize ===
loadClients();
</script>
</body>
</html>
