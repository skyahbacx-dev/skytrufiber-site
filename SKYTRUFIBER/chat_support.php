<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['name'])) {
  header("Location: skytrufiber.php");
  exit;
}

$username = $_SESSION['name'];
$email = $_SESSION['email'] ?? '';
$clientStmt = $conn->prepare("SELECT id, assigned_csr FROM clients WHERE name = :n LIMIT 1");
$clientStmt->execute([':n' => $username]);
$client = $clientStmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
  die("❌ Client not found in the system.");
}

$client_id = $client['id'];
$assigned_csr = $client['assigned_csr'] ?? 'Unassigned';

// Fetch CSR name (if assigned)
$csr_name = '';
if ($assigned_csr !== 'Unassigned') {
  $csrStmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :csr LIMIT 1");
  $csrStmt->execute([':csr' => $assigned_csr]);
  $csr_name = $csrStmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber - Live Chat Support</title>
<style>
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: #e6faff;
  margin: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
}

.chat-box {
  background: #fff;
  width: 90%;
  max-width: 600px;
  border-radius: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.header {
  background: #0099cc;
  color: #fff;
  padding: 15px;
  text-align: center;
  font-weight: bold;
  font-size: 18px;
}

.messages {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
  background: #f2fcff;
}

.message {
  margin: 10px 0;
  padding: 10px 14px;
  border-radius: 10px;
  max-width: 70%;
  word-wrap: break-word;
  position: relative;
  clear: both;
}

.client {
  background: #d9f9d9;
  float: right;
  text-align: right;
}

.csr {
  background: #e6f2ff;
  float: left;
  text-align: left;
}

.timestamp {
  font-size: 11px;
  color: #777;
  margin-top: 4px;
}

.input-area {
  display: flex;
  padding: 10px;
  border-top: 1px solid #ddd;
  background: #fff;
}

.input-area input {
  flex: 1;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 8px;
  outline: none;
}

.input-area button {
  margin-left: 8px;
  padding: 10px 15px;
  border: none;
  border-radius: 8px;
  background: #0099cc;
  color: white;
  font-weight: bold;
  cursor: pointer;
}

.input-area button:hover { background: #007a99; }

</style>
</head>
<body>

<div class="chat-box">
  <div class="header">
    👋 Welcome, <?= htmlspecialchars($username) ?>  
    <?php if ($csr_name): ?><br><small>Connected to <?= htmlspecialchars($csr_name) ?></small><?php endif; ?>
  </div>

  <div id="messages" class="messages">
    <!-- Messages will load dynamically -->
  </div>

  <div class="input-area">
    <input type="text" id="message" placeholder="Type your message..." autocomplete="off">
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
const clientId = <?= (int)$client_id ?>;
const username = <?= json_encode($username) ?>;

function loadMessages() {
  fetch('load_chat.php?client_id=' + clientId)
    .then(res => res.json())
    .then(data => {
      const msgBox = document.getElementById('messages');
      msgBox.innerHTML = '';
      data.forEach(m => {
        const div = document.createElement('div');
        div.classList.add('message');
        div.classList.add(m.sender_type === 'csr' ? 'csr' : 'client');
        div.innerHTML = `
          ${m.message}
          <div class="timestamp">${new Date(m.time).toLocaleString()}</div>
        `;
        msgBox.appendChild(div);
      });
      msgBox.scrollTop = msgBox.scrollHeight;
    });
}

function sendMessage() {
  const msg = document.getElementById('message').value.trim();
  if (!msg) return;

  const body = new URLSearchParams();
  body.append('sender_type', 'client');
  body.append('message', msg);
  body.append('username', username);

  fetch('save_chat.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body
  }).then(() => {
    document.getElementById('message').value = '';
    loadMessages();
  });
}

// Load messages every 2 seconds
setInterval(loadMessages, 2000);
window.onload = loadMessages;
</script>

</body>
</html>
