<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber Live Support</title>
<style>

/* ========================================= */
/* 🌈 GLOBAL DESIGN IMPROVED                 */
/* ========================================= */

body {
  margin: 0;
  padding: 0;
  font-family: 'Segoe UI', Arial, sans-serif;
  background: linear-gradient(135deg, #bfe6ff, #e7f6ff);
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden;
  position: relative;
}

/* Watermark logo */
body::before {
  content: "";
  position: absolute;
  inset: 0;
  background: url('../SKYTRUFIBER.png') center center no-repeat;
  background-size: 350px;
  opacity: 0.10;
  filter: grayscale(100%);
  z-index: 0;
}

/* Outer chat container */
.chat-box-wrapper {
  position: relative;
  z-index: 1;
  width: 430px;
  max-width: 94%;
  height: 89vh;
  background: white;
  border-radius: 18px;
  box-shadow: 0 8px 28px rgba(0,0,0,0.18);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(0,0,0,0.05);
}

/* ========================================= */
/* 🌟 HEADER                                  */
/* ========================================= */
.header {
  background: linear-gradient(135deg, #0088cc, #00a5dd);
  padding: 16px;
  color: #fff;
  text-align: center;
  border-bottom: 2px solid rgba(255,255,255,0.3);
}

.header-title {
  font-size: 18px;
  font-weight: 600;
}

.header-sub {
  font-size: 13px;
  opacity: 0.85;
}

/* Logo on top of header */
.chat-logo {
  width: 110px;
  height: 110px;
  object-fit: contain;
  margin: 10px auto 0;
  display: block;
  filter: drop-shadow(0px 3px 5px rgba(0,0,0,0.2));
}

/* ========================================= */
/* 💬 MESSAGES AREA                           */
/* ========================================= */
.messages {
  flex: 1;
  padding: 20px 15px;
  overflow-y: auto;
  background: #f7fcff;
  background-image: url("../SKYTRUFIBER.png");
  background-repeat: no-repeat;
  background-position: center;
  background-size: 250px;
  background-blend-mode: lighten;
}

.message-bubble {
  padding: 12px 16px;
  border-radius: 18px;
  margin: 8px 0;
  max-width: 78%;
  word-break: break-word;
  line-height: 1.4;
  font-size: 14.3px;
  position: relative;
  box-shadow: 0 3px 7px rgba(0,0,0,0.08);
}

/* CSR messages */
.message-csr {
  background: #e1f1ff;
  border-left: 4px solid #0080c6;
  color: #003355;
  align-self: flex-start;
}

/* User messages */
.message-user {
  background: #ccffd8;
  border-right: 4px solid #189444;
  align-self: flex-end;
  color: #083713;
}

/* Timestamp */
.timestamp {
  font-size: 11px;
  opacity: 0.6;
  margin-top: 6px;
}

/* ========================================= */
/* ✏️ INPUT BAR                               */
/* ========================================= */
.input-area {
  display: flex;
  padding: 12px;
  background: #f4f4f4;
  border-top: 1px solid #ddd;
}

.input-area input {
  flex: 1;
  border: none;
  outline: none;
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
  background: #fff;
}

.btn-send {
  margin-left: 10px;
  background: #0099cc;
  color: #fff;
  border: none;
  padding: 12px 18px;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.2s;
}

.btn-send:hover {
  background: #007eb1;
}

/* Scrollbar */
.messages::-webkit-scrollbar {
  width: 8px;
}
.messages::-webkit-scrollbar-thumb {
  background: #b8dcf0;
  border-radius: 8px;
}

/* Mobile */
@media (max-width: 500px) {
  .chat-box-wrapper {
    height: 92vh;
  }
}
</style>
</head>

<body>

<div class="chat-box-wrapper">

  <img src="../SKYTRUFIBER.png" class="chat-logo">

  <!-- Header -->
  <div class="header">
    <div class="header-title">Welcome, <?= htmlspecialchars($username) ?></div>
    <div class="header-sub">Connected to <?= $csr_name_display ?></div>
  </div>

  <!-- Messages -->
  <div class="messages" id="chatBox"></div>

  <!-- Input -->
  <div class="input-area">
    <input type="text" id="message" placeholder="Type your message...">
    <button class="btn-send" onclick="sendMessage()">Send</button>
  </div>

</div>

<script>
const username = <?= json_encode($username) ?>;
const chatBox = document.getElementById('chatBox');

// Load messages
function loadChat() {
  fetch(`load_chat.php?client=${encodeURIComponent(username)}`)
    .then(r=>r.json())
    .then(data=>{
      chatBox.innerHTML = "";
      data.forEach(msg=>{
        const wrap = document.createElement('div');
        wrap.className = "message-bubble " + (msg.sender_type === "csr" ? "message-csr" : "message-user");
        wrap.innerHTML = `
          ${msg.message}
          <div class="timestamp">${new Date(msg.created_at).toLocaleString()}</div>
        `;
        chatBox.appendChild(wrap);
      });
      chatBox.scrollTop = chatBox.scrollHeight;
    });
}

function sendMessage() {
  const text = document.getElementById('message').value.trim();
  if (!text) return;
  fetch("save_chat.php", {
    method: "POST",
    headers: {"Content-Type":"application/x-www-form-urlencoded"},
    body: new URLSearchParams({
      sender_type: "client",
      message: text,
      client: username
    })
  }).then(()=>{
    document.getElementById('message').value="";
    loadChat();
  });
}

setInterval(loadChat, 1500);
window.onload = loadChat;
</script>

</body>
</html>
