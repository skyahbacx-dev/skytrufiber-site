<?php
session_start();
include '../db_connect.php';

// Ensure CSR is logged in
if (!isset($_SESSION['csr_user'])) {
  header("Location: csr_login.php");
  exit;
}

$csr_user = $_SESSION['csr_user'];
$csr_fullname = $_SESSION['csr_fullname'] ?? $csr_user;

// Fetch clients list with their assigned CSR
$stmt = $conn->query("
  SELECT id, name, assigned_csr
  FROM clients
  ORDER BY name ASC
");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activeClientId = $_GET['client_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Chat — SkyTruFiber</title>
<style>
body {
  font-family: Arial, sans-serif;
  background: #e9ffe9;
  margin: 0;
  display: flex;
  height: 100vh;
}
.sidebar {
  width: 250px;
  background: #009900;
  color: white;
  padding: 15px;
  display: flex;
  flex-direction: column;
}
.sidebar h2 {
  text-align: center;
  font-size: 18px;
  margin-bottom: 10px;
}
.client-list {
  flex: 1;
  overflow-y: auto;
  background: #007a00;
  border-radius: 8px;
  padding: 8px;
}
.client {
  display: block;
  background: #00b300;
  color: #fff;
  padding: 10px;
  margin: 6px 0;
  text-align: center;
  border-radius: 6px;
  text-decoration: none;
  transition: background 0.3s;
}
.client:hover { background: #33cc33; }
.active-client { background: #004d00 !important; font-weight: bold; }
.logout {
  background: #ff3333;
  padding: 8px;
  text-align: center;
  border-radius: 6px;
  color: white;
  text-decoration: none;
  font-weight: bold;
  margin-top: 10px;
}
.chat-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #fff;
  border-left: 2px solid #00aa00;
}
header {
  background: #00aa00;
  color: white;
  padding: 10px;
  font-size: 18px;
}
#messages {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  background: #f5fff5;
}
.message {
  margin: 8px 0;
  padding: 10px;
  border-radius: 10px;
  max-width: 70%;
  display: inline-block;
  clear: both;
}
.client-msg { background: #e0fbe0; float: left; }
.csr-msg { background: #009900; color: white; float: right; }
#input-area {
  display: flex;
  border-top: 1px solid #ccc;
}
#input-area input {
  flex: 1;
  padding: 10px;
  border: none;
  outline: none;
}
#input-area button {
  background: #009900;
  color: white;
  border: none;
  padding: 10px 20px;
  cursor: pointer;
}
#input-area button:hover { background: #007a00; }
.no-client {
  text-align: center;
  margin-top: 50px;
  color: #555;
  font-size: 18px;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h2>👩‍💻 <?= htmlspecialchars($csr_fullname) ?></h2>
  <div class="client-list">
    <?php foreach ($clients as $row): 
      $active = ($row['id'] == $activeClientId) ? "active-client" : ""; ?>
      <a href="?client_id=<?= $row['id'] ?>" class="client <?= $active ?>">
        <?= htmlspecialchars($row['name']) ?><br>
        <small>Assigned: <?= htmlspecialchars($row['assigned_csr'] ?: 'Unassigned') ?></small>
      </a>
    <?php endforeach; ?>
  </div>
  <a href="csr_logout.php" class="logout">🚪 Logout</a>
</div>

<!-- Chat Section -->
<div class="chat-container">
  <header>
    <?php if ($activeClientId): ?>
      Chat with <?= htmlspecialchars($clients[array_search($activeClientId, array_column($clients, 'id'))]['name'] ?? '') ?>
    <?php else: ?>
      Select a client to start chatting
    <?php endif; ?>
  </header>

  <div id="messages">
    <?php if (!$activeClientId): ?>
      <div class="no-client">👈 Choose a client from the sidebar</div>
    <?php endif; ?>
  </div>

  <?php if ($activeClientId): ?>
  <div id="input-area">
    <input type="text" id="message" placeholder="Type a reply...">
    <button onclick="sendCSR()">Send</button>
  </div>
  <?php endif; ?>
</div>

<script>
const clientId = "<?= htmlspecialchars($activeClientId) ?>";
const csrUser = "<?= htmlspecialchars($csr_user) ?>";
const csrFullname = "<?= htmlspecialchars($csr_fullname) ?>";

function loadChat() {
  if (!clientId) return;
  fetch('../SKYTRUFIBER/load_chat.php?client_id=' + clientId)
    .then(res => res.json())
    .then(data => {
      const msgBox = document.getElementById('messages');
      msgBox.innerHTML = '';
      data.forEach(m => {
        const div = document.createElement('div');
        div.classList.add('message');
        div.classList.add(m.sender_type === 'csr' ? 'csr-msg' : 'client-msg');
        div.textContent = `${m.message}`;
        msgBox.appendChild(div);
      });
      msgBox.scrollTop = msgBox.scrollHeight;
    });
}

function sendCSR() {
  const message = document.getElementById('message').value.trim();
  if (!message) return;

  const body = new URLSearchParams();
  body.append('sender_type', 'csr');
  body.append('message', message);
  body.append('csr_user', csrUser);
  body.append('csr_fullname', csrFullname);
  body.append('client_id', clientId);

  fetch('../SKYTRUFIBER/save_chat.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body
  }).then(() => {
    document.getElementById('message').value = '';
    loadChat();
  });
}

if (clientId) {
  setInterval(loadChat, 1500);
  window.onload = loadChat;
}
</script>
</body>
</html>
