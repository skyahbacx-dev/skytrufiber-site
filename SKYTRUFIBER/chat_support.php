<?php
include '../db_connect.php';
$username = $_GET['username'] ?? 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Support Chat</title>
<style>
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

/* Watermark behind everything */
body::before {
  content: "";
  background: url('../SKYTRUFIBER.png') no-repeat center center;
  background-size: 500px auto;
  opacity: 0.05;
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  z-index: 0;
}

/* Chat container */
.chat-wrapper {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 15px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  width: 450px;
  max-width: 95%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  z-index: 1;
}

/* Logo above chat box */
.logo-header {
  text-align: center;
  background: #ffffff;
  padding: 15px 10px 0;
}
.logo-header img {
  width: 120px;
  border-radius: 50%;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Chat header */
.chat-header {
  background: #0099cc;
  color: white;
  padding: 12px;
  text-align: center;
  font-weight: bold;
  font-size: 16px;
  border-top: 2px solid #007a99;
}

/* Chat messages area */
.chat-box {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
  background: #f4fbff url('../SKYTRUFIBER.png') repeat; /* Optional background pattern */
  background-size: 120px;
}

/* Message styling */
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
}
.csr {
  background: #e3f2fd;
  border-left: 3px solid #0099cc;
  color: #004466;
  float: left;
}

/* Input area */
.chat-input {
  display: flex;
  border-top: 1px solid #ccc;
}
.chat-input input {
  flex: 1;
  padding: 12px;
  border: none;
  outline: none;
  font-size: 14px;
}
.chat-input button {
  background: #0099cc;
  color: white;
  border: none;
  padding: 12px 20px;
  cursor: pointer;
  font-weight: bold;
}
.chat-input button:hover {
  background: #007a99;
}

/* Timestamp */
.timestamp {
  display: block;
  font-size: 11px;
  color: #777;
  margin-top: 3px;
}

/* Scrollbar styling */
.chat-box::-webkit-scrollbar {
  width: 8px;
}
.chat-box::-webkit-scrollbar-thumb {
  background: #b0d4e3;
  border-radius: 10px;
}
</style>
</head>
<body>

<div class="chat-wrapper">
  <!-- Logo above chat -->
  <div class="logo-header">
    <img src="../SKYTRUFIBER.png" alt="SkyTruFiber Logo">
  </div>

  <!-- Chat header -->
  <div class="chat-header">
    👋 Welcome, <?= htmlspecialchars($username) ?><br>
    <small>Connected to CSR WALDO</small>
  </div>

  <!-- Chat messages -->
  <div class="chat-box" id="chatBox">
    <!-- Example messages -->
    <div class="message csr">
      👋 Hi <?= htmlspecialchars($username) ?>! This is CSR WALDO from SkyTruFiber.<br>
      How can I assist you today?
      <span class="timestamp">11/09/2025, 5:15 AM</span>
    </div>

    <div class="message user">
      I HAVE A SLOW INTERNET TODAY. CAN YOU HELP ME OUT?
      <span class="timestamp">11/09/2025, 5:37 AM</span>
    </div>
  </div>

  <!-- Input -->
  <div class="chat-input">
    <input type="text" placeholder="Type your message..." id="message">
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
