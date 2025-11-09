<?php
session_start();
include '../db_connect.php';

// Identify user (you might have stored this in session after login)
$username = $_SESSION['name'] ?? 'Guest-' . rand(1000, 9999);
$_SESSION['username'] = $username;

// Get or create client_id
$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([':name' => $username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    $conn->prepare("INSERT INTO clients (name, assigned_csr, created_at) VALUES (:n, 'Unassigned', NOW())")->execute([':n' => $username]);
    $client_id = $conn->lastInsertId();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber — Customer Chat</title>
<style>
body {
  font-family: 'Segoe UI', Arial, sans-serif;
  background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  margin: 0;
}
.chat-container {
  width: 95%;
  max-width: 600px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  height: 80vh;
}
.header {
  background: #0099cc;
  color: #fff;
  text-align: center;
  padding: 15px;
  font-weight: bold;
  border-radius: 12px 12px 0 0;
}
.chat-box {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
  background: #f7f9fa;
}
.msg {
  margin: 8px 0;
  padding: 10px 12px;
  border-radius: 8px;
  max-width: 75%;
  line-height: 1.4;
  font-size: 14px;
  word-wrap: break-word;
}
.msg.client {
  background: #d0f0ff;
  align-self: flex-end;
}
.msg.csr {
  background: #d5ffd6;
  align-self: flex-start;
}
.msg.system {
  text-align: center;
  font-style: italic;
  background: #fff8cc;
}
.input {
  display: flex;
  padding: 10px;
  background: #f2f2f2;
  border-top: 1px solid #ccc;
}
.input textarea {
  flex: 1;
  resize: none;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
  height: 45px;
}
.input button {
  padding: 10px 16px;
  background: #0099cc;
  color: #fff;
  border: none;
  border-radius: 8px;
  margin-left: 8px;
  cursor: pointer;
  font-weight: bold;
}
.input button:hover { background: #007a99; }
</style>
</head>
<body>

<div class="chat-container">
  <div class="header">💬 SkyTruFiber Chat Support</div>
  <div class="chat-box" id="chat-box"></div>
  <div class="input">
    <textarea id="msg" placeholder="Type your message..." onkeypress="handleEnter(event)"></textarea>
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
const client_id = <?= (int)$client_id ?>;
const username = "<?= htmlspecialchars($username) ?>";
const chatBox = document.getElementById('chat-box');

// Load chat messages
function loadChat() {
  fetch(`load_chat.php?client_id=${client_id}`)
    .then(res => res.json())
    .then(data => {
      chatBox.innerHTML = '';
      data.forEach(m => {
        const div = document.createElement('div');
        div.className = 'msg ' + (m.sender_type === 'csr' ? 'csr' : 'client');
        div.textContent = m.message;
        chatBox.appendChild(div);
      });
      chatBox.scrollTop = chatBox.scrollHeight;
    });
}

// Send message
function sendMessage() {
  const msg = document.getElementById('msg').value.trim();
  if (msg === '') return;

  const data = new URLSearchParams();
  data.append('sender_type', 'client');
  data.append('message', msg);
  data.append('username', username);

  fetch('save_chat.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: data.toString()
  }).then(() => {
    document.getElementById('msg').value = '';
    loadChat();
  });
}

// Allow Enter to send
function handleEnter(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
}

// Auto-refresh every 2 seconds
setInterval(loadChat, 2000);
window.onload = loadChat;
</script>

</body>
</html>
