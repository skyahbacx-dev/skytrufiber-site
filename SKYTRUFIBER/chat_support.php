<?php
include '../db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

$username = $_GET['username'] ?? 'Guest';

/* =======================
   FETCH ASSIGNED CSR
   ======================= */
try {
    $stmt = $conn->prepare("
        SELECT assigned_csr, csr_fullname 
        FROM chat 
        WHERE client_id = (
            SELECT id FROM clients WHERE username = :username LIMIT 1
        )
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([':username' => $username]);
    $chatInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    $csr_fullname = $chatInfo['csr_fullname'] ?? 'No CSR Assigned';
    $csr_name_display = $csr_fullname !== 'No CSR Assigned' ? htmlspecialchars($csr_fullname) : 'CSR Support Team';
} catch (PDOException $e) {
    $csr_name_display = 'CSR Support';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Support Chat</title>
<style>
/* ===== BASE STYLING ===== */
body {
  font-family: 'Segoe UI', Arial, sans-serif;
  background: linear-gradient(to bottom right, #cceeff, #e6f7ff);
  margin: 0;
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  overflow: hidden;
}

/* ===== PAGE WATERMARK ===== */
body::before {
  content: "";
  background: url('../SKYTRUFIBER.png') no-repeat center center;
  background-size: clamp(250px, 35vw, 550px);
  opacity: 0.05;
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  z-index: 0;
  filter: grayscale(100%);
}

/* ===== CHAT WRAPPER ===== */
.chat-wrapper {
  background: rgba(255, 255, 255, 0.97);
  border-radius: 15px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  width: 450px;
  max-width: 95%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  z-index: 1;
}

/* ===== LOGO ABOVE CHAT ===== */
.logo-header {
  text-align: center;
  background: #ffffff;
  padding: 20px 10px 10px;
}
.logo-header img {
  width: 120px;
  border-radius: 50%;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}

/* ===== CHAT HEADER ===== */
.chat-header {
  background: #0099cc;
  color: white;
  padding: 12px;
  text-align: center;
  font-weight: bold;
  font-size: 16px;
  border-top: 2px solid #007a99;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* ===== CHAT AREA ===== */
.chat-box {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
  background: #f2fbff url('../SKYTRUFIBER.png') no-repeat center center fixed;
  background-size: 280px auto;
  background-blend-mode: lighten;
}

/* ===== MESSAGE STYLING ===== */
.message {
  margin: 10px 0;
  padding: 10px 15px;
  border-radius: 10px;
  display: inline-block;
  max-width: 80%;
  word-wrap: break-word;
  font-size: 14px;
  line-height: 1.4;
}
.user {
  background: #dfffe2;
  align-self: flex-end;
  float: right;
  border: 1px solid #aee1b6;
}
.csr {
  background: #e3f2fd;
  border-left: 3px solid #0099cc;
  color: #004466;
  float: left;
  border: 1px solid #b3d9f7;
}

/* ===== TIMESTAMP ===== */
.timestamp {
  display: block;
  font-size: 11px;
  color: #777;
  margin-top: 3px;
}

/* ===== INPUT AREA ===== */
.chat-input {
  display: flex;
  border-top: 1px solid #ccc;
  background: #f9f9f9;
}
.chat-input input {
  flex: 1;
  padding: 12px;
  border: none;
  outline: none;
  font-size: 14px;
  background: transparent;
}
.chat-input button {
  background: #0099cc;
  color: white;
  border: none;
  padding: 12px 20px;
  cursor: pointer;
  font-weight: bold;
  transition: 0.3s;
}
.chat-input button:hover {
  background: #007a99;
}

/* ===== SCROLLBAR ===== */
.chat-box::-webkit-scrollbar { width: 8px; }
.chat-box::-webkit-scrollbar-thumb {
  background: #b0d4e3;
  border-radius: 10px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 500px) {
  .chat-wrapper { width: 95%; }
  .logo-header img { width: 90px; }
  .chat-header { font-size: 15px; }
  body::before {
    background-size: 250px;
    opacity: 0.07;
  }
}
</style>
</head>
<body>

<div class="chat-wrapper">
  <!-- Logo above chat -->
  <div class="logo-header">
    <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
  </div>

  <!-- Dynamic Chat Header -->
  <div class="chat-header">
    👋 Welcome, <?= htmlspecialchars($username) ?><br>
    <small>Connected to <?= $csr_name_display ?></small>
  </div>

  <!-- Chat Messages -->
  <div class="chat-box" id="chatBox">
    <div class="message csr">
      👋 Hi <?= htmlspecialchars($username) ?>! This is <?= $csr_name_display ?> from SkyTruFiber.<br>
      How can I assist you today?
      <span class="timestamp"><?= date("n/j/Y, g:i A") ?></span>
    </div>
  </div>

  <!-- Input Section -->
  <div class="chat-input">
    <input type="text" id="message" placeholder="Type your message...">
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
function sendMessage() {
  const box = document.getElementById('chatBox');
  const input = document.getElementById('message');
  const msg = input.value.trim();
  if (!msg) return;

  const div = document.createElement('div');
  div.className = 'message user';
  div.innerHTML = msg + `<span class="timestamp">${new Date().toLocaleTimeString()}</span>`;
  box.appendChild(div);
  input.value = '';
  box.scrollTop = box.scrollHeight;
}
</script>

</body>
</html>
