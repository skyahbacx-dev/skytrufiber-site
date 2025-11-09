<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['name'])) {
    header("Location: ../CSR/csr_login.php");
    exit;
}

$name = $_SESSION['name'];
$email = $_SESSION['email'];
$concern = $_SESSION['concern'];
$account_number = $_SESSION['user'];

// ✅ Insert auto-greeting if new session
if (!isset($_SESSION['greeted'])) {
    $greeting = "Hi $name 👋! Thanks for contacting SkyTruFiber. We received your concern: \"$concern\". A CSR will be with you shortly.";
    $stmt = $conn->prepare("INSERT INTO chat (account_number, sender, message, created_at) VALUES (:account_number, 'system', :message, NOW())");
    $stmt->execute([':account_number' => $account_number, ':message' => $greeting]);
    $_SESSION['greeted'] = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Chat Support</title>
<style>
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: #e6fff2;
  margin: 0;
  padding: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}
.chat-container {
  background: #fff;
  width: 450px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.chat-header {
  background: #009966;
  color: #fff;
  padding: 15px;
  text-align: center;
  font-weight: bold;
}
.chat-box {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
  background: #f9f9f9;
}
.message {
  margin: 8px 0;
  padding: 10px;
  border-radius: 10px;
  max-width: 80%;
  word-wrap: break-word;
}
.message.customer { background: #d0f0ff; align-self: flex-end; }
.message.csr { background: #d5ffd6; align-self: flex-start; }
.message.system { background: #ffe0b3; text-align: center; font-style: italic; }
.chat-input {
  display: flex;
  padding: 10px;
  background: #f2f2f2;
}
.chat-input input {
  flex: 1;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 8px;
}
.chat-input button {
  background: #009966;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 10px 15px;
  margin-left: 8px;
  cursor: pointer;
}
.chat-input button:hover { background: #007a55; }
</style>
</head>
<body>

<div class="chat-container">
  <div class="chat-header">
    SkyTruFiber Customer Chat
  </div>
  <div class="chat-box" id="chat-box">
    <!-- Chat messages will load here -->
  </div>
  <div class="chat-input">
    <input type="text" id="message" placeholder="Type your message...">
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
// 🔄 Load messages every 2 seconds
function loadChat() {
  fetch('load_chat.php')
    .then(response => response.text())
    .then(data => {
      document.getElementById('chat-box').innerHTML = data;
      const chatBox = document.getElementById('chat-box');
      chatBox.scrollTop = chatBox.scrollHeight; // auto scroll
    });
}

function sendMessage() {
  const msg = document.getElementById('message').value.trim();
  if (msg === '') return;
  
  fetch('save_chat.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'message=' + encodeURIComponent(msg)
  })
  .then(() => {
    document.getElementById('message').value = '';
    loadChat();
  });
}

setInterval(loadChat, 2000);
window.onload = loadChat;
</script>

</body>
</html>
