<?php
session_start();
$conn = new mysqli("localhost", "root", "", "skytrufiber_db");

if (!isset($_SESSION['csr_user'])) {
  header("Location: csr_login.php");
  exit;
}

$csr_name = $_SESSION['csr_user'];

// Get all distinct clients
$clients = $conn->query("SELECT DISTINCT username FROM chat WHERE username != 'CSR' ORDER BY username ASC");

// If a client is selected
$activeClient = $_GET['client'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>CSR Chat Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #e9ffe9;
      margin: 0;
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    /* Sidebar for clients */
    .sidebar {
      width: 260px;
      background: #009900;
      color: white;
      padding: 15px;
      display: flex;
      flex-direction: column;
    }

    .sidebar h2 {
      text-align: center;
      font-size: 20px;
      margin-bottom: 15px;
    }

    .client-list {
      flex: 1;
      overflow-y: auto;
      background: #007a00;
      border-radius: 8px;
      padding: 8px;
    }

    .client {
      background: #00b300;
      padding: 10px;
      margin: 6px 0;
      border-radius: 6px;
      cursor: pointer;
      text-align: center;
      color: white;
      text-decoration: none;
      display: block;
      transition: background 0.3s;
    }

    .client:hover {
      background: #33cc33;
    }

    .active-client {
      background: #004d00 !important;
      font-weight: bold;
    }

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

    /* Chat container */
    .chat-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: #fff;
      border-left: 2px solid #00aa00;
      position: relative;
    }

    header {
      background: #00aa00;
      color: white;
      padding: 10px;
      font-size: 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
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

    .user {
      background: #e0fbe0;
      float: left;
    }

    .csr {
      background: #009900;
      color: white;
      float: right;
    }

    #input-area {
      display: flex;
      border-top: 1px solid #ccc;
    }

    #input-area input {
      flex: 1;
      padding: 10px;
      border: none;
      outline: none;
      font-size: 14px;
    }

    #input-area button {
      background: #009900;
      color: white;
      border: none;
      padding: 10px 20px;
      cursor: pointer;
    }

    #input-area button:hover {
      background: #007a00;
    }

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
    <h2>👩‍💻 <?= htmlspecialchars($csr_name) ?></h2>
    <div class="client-list">
      <?php while ($row = $clients->fetch_assoc()): 
        $selected = ($row['username'] === $activeClient) ? "active-client" : ""; ?>
        <a href="?client=<?= urlencode($row['username']) ?>" class="client <?= $selected ?>">
          <?= htmlspecialchars($row['username']) ?>
        </a>
      <?php endwhile; ?>
    </div>
    <a href="csr_logout.php" class="logout">🚪 Logout</a>
  </div>

  <!-- Chat Section -->
  <div class="chat-container">
    <header>
      <?php if ($activeClient): ?>
        Chat with <strong><?= htmlspecialchars($activeClient) ?></strong>
      <?php else: ?>
        Select a client to start chatting
      <?php endif; ?>
    </header>

    <div id="messages">
      <?php if (!$activeClient): ?>
        <div class="no-client">👈 Choose a client from the sidebar</div>
      <?php endif; ?>
    </div>

    <?php if ($activeClient): ?>
    <div id="input-area">
      <input type="text" id="message" placeholder="Type a reply...">
      <button onclick="sendCSR()">Send</button>
    </div>
    <?php endif; ?>
  </div>

  <script>
  const client = "<?= $activeClient ?>";

  function loadChat() {
    if (!client) return;
    fetch('load_chat.php?client=' + encodeURIComponent(client))
      .then(res => res.json())
      .then(data => {
        const msgBox = document.getElementById('messages');
        msgBox.innerHTML = '';
        data.forEach(m => {
          const div = document.createElement('div');
          div.classList.add('message');
          div.classList.add(m.username.toLowerCase() === 'csr' ? 'csr' : 'user');
          div.textContent = `${m.username}: ${m.message}`;
          msgBox.appendChild(div);
        });
        msgBox.scrollTop = msgBox.scrollHeight;
      });
  }

  function sendCSR() {
    const message = document.getElementById('message').value.trim();
    if (!message) return;

    fetch('save_chat.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `username=CSR&message=${encodeURIComponent(message)}&client=${encodeURIComponent(client)}`
    }).then(() => {
      document.getElementById('message').value = '';
      loadChat();
    });
  }

  if (client) {
    setInterval(loadChat, 1500);
    window.onload = loadChat;
  }
  </script>
</body>
</html>
