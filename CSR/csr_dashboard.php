<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];
$logoPath = file_exists("AHBALOGO.png") ? "AHBALOGO.png" : "../SKYTRUFIBER/AHBALOGO.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_user) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=3">
</head>
<body>

<header>
    <div class="header-left">
        <img src="<?= $logoPath ?>" alt="Logo" class="logo">
        <h1>CSR Dashboard — <?= htmlspecialchars($csr_user) ?></h1>
    </div>
    <a href="csr_logout.php" class="logout">Logout</a>
</header>

<div class="container">
    <aside class="sidebar">
        <button class="tab active" onclick="loadClients('all')">💬 All Clients</button>
        <button class="tab" onclick="loadClients('mine')">👤 My Clients</button>
        <button class="tab" onclick="loadReminders()">⏰ Reminders</button>
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
// === VARIABLES ===
let currentClient = null;
let refreshInterval;

// === LOAD CLIENTS ===
function loadClients(type='all') {
    fetch(`csr_dashboard_ajax.php?action=load_clients&type=${type}`)
        .then(res => res.json())
        .then(data => {
            const sidebar = document.querySelector('.sidebar');
            sidebar.innerHTML = `
                <button class="tab ${type === 'all' ? 'active' : ''}" onclick="loadClients('all')">💬 All Clients</button>
                <button class="tab ${type === 'mine' ? 'active' : ''}" onclick="loadClients('mine')">👤 My Clients</button>
                <button class="tab" onclick="loadReminders()">⏰ Reminders</button>
                <button class="tab" onclick="window.location.href='survey_responses.php'">📝 Survey Responses</button>
                <button class="tab" onclick="window.location.href='update_profile.php'">👤 Update Profile</button>
            `;

            data.forEach(client => {
                const item = document.createElement('div');
                item.className = 'client-item';
                item.innerHTML = `
                    <img src="${client.name[0].toUpperCase() <= 'M' ? 'CSR/lion.PNG' : 'CSR/penguin.PNG'}" class="client-avatar">
                    <div>
                        <strong>${client.name}</strong><br>
                        <small>${client.status}</small>
                    </div>`;
                item.onclick = () => selectClient(client.id, client.name);
                sidebar.appendChild(item);
            });
        });
}

// === SELECT CLIENT ===
function selectClient(id, name) {
    currentClient = id;
    document.getElementById('clientName').innerText = name;
    document.getElementById('clientAvatar').src = (name[0].toUpperCase() <= 'M') ? 'CSR/lion.PNG' : 'CSR/penguin.PNG';
    loadChat();
    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(loadChat, 3000);
}

// === LOAD CHAT ===
function loadChat() {
    if (!currentClient) return;
    fetch(`csr_dashboard_ajax.php?action=load_chat&client_id=${currentClient}`)
        .then(res => res.json())
        .then(messages => {
            const chat = document.getElementById('chatMessages');
            chat.innerHTML = '';
            messages.forEach(msg => {
                const div = document.createElement('div');
                div.className = `message ${msg.sender_type}`;
                div.innerHTML = `
                    <div class="bubble">
                        <strong>${msg.sender_name}:</strong> ${msg.message}
                        <div class="meta">${msg.created_at}</div>
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
    fetch(`csr_dashboard_ajax.php?action=send_msg`, {
        method: 'POST',
        body: new URLSearchParams({ client_id: currentClient, message: msg })
    }).then(() => {
        document.getElementById('msgInput').value = '';
        loadChat();
    });
}

// === TYPING ANIMATION ===
function handleTyping(e) {
    if (e.key === 'Enter') sendMessage();
}

loadClients();
</script>
</body>
</html>
