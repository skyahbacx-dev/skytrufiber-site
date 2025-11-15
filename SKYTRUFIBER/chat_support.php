<?php
include '../db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

// Username of client
$username = $_GET['username'] ?? 'Guest';

// PH timezone
date_default_timezone_set("Asia/Manila");

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>SkyTruFiber — Support Chat</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
/* Messenger-style UI */

:root{
  --bg: #e5e8f0;
  --header: #0084FF;
  --outgoing: #0084FF;
  --incoming: #f0f0f0;
  --text-on-blue: #fff;
  --text-on-gray: #0a0a0a;
  --shadow: 0 10px 28px rgba(0,0,0,.18);
  --border: 1px solid rgba(0,0,0,0.06);
}

/* ... All your existing CSS unchanged ... */

.media-img{
  max-width: 200px;
  border-radius: 12px;
  margin-top: 5px;
}
.media-video{
  max-width: 240px;
  border-radius: 12px;
  margin-top: 5px;
}
</style>
</head>
<body>

<div class="chat-wrap">
  <div class="chat-header">
    <div class="logo"><img src="../SKYTRUFIBER.png" width="34"></div>
    <div class="head-text">
      <div class="head-title">SkyTruFiber Support</div>
      <div class="head-sub">Chat Support Online</div>
    </div>
  </div>

  <div id="chatBox" class="messages"></div>

  <div class="typing" id="typing"><span>Typing</span><div class="dot"></div><div class="dot"></div><div class="dot"></div></div>

  <div class="input-bar">
    <input id="message" placeholder="Write a message…" autocomplete="off" />
    <input type="file" id="fileUpload" accept="image/*,video/*" style="display:none;" />
    <button onclick="document.getElementById('fileUpload').click()">📎</button>
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
const USERNAME = <?= json_encode($username, JSON_UNESCAPED_UNICODE) ?>;
const chatBox  = document.getElementById('chatBox');
const inputEl  = document.getElementById('message');
const fileEl   = document.getElementById('fileUpload');

let typingTimer;

function renderRow(m){
  const isCSR = m.sender_type === 'csr';
  const row = document.createElement('div');
  row.className = isCSR ? 'msg-row msg-in' : 'msg-row msg-out';

  const av = document.createElement('div');
  av.className = 'avatar';
  av.textContent = isCSR ? 'C' : USERNAME.charAt(0).toUpperCase();

  const bubble = document.createElement('div');
  bubble.className = 'bubble';

  if (m.message) bubble.appendChild(document.createTextNode(m.message));

  if (m.media_path){
    if (m.media_type === 'image'){
      const img = document.createElement('img');
      img.src = m.media_path;
      img.className = 'media-img';
      bubble.appendChild(img);
    }
    if (m.media_type === 'video'){
      const v = document.createElement('video');
      v.src = m.media_path;
      v.controls = true;
      v.className = 'media-video';
      bubble.appendChild(v);
    }
  }

  const t = document.createElement('div');
  t.className = 'time';
  t.textContent = m.created_at;
  bubble.appendChild(t);

  row.appendChild(av);
  row.appendChild(bubble);
  chatBox.appendChild(row);
}

function loadChat(){
  fetch('load_chat.php?client=' + encodeURIComponent(USERNAME))
    .then(r => r.json())
    .then(list => {
      chatBox.innerHTML = '';
      list.forEach(renderRow);
      chatBox.scrollTop = chatBox.scrollHeight;
    });
}

function sendMessage(){
  const msg = inputEl.value.trim();
  if (!msg && !fileEl.files.length) return;

  const form = new FormData();
  form.append('client', USERNAME);
  form.append('sender_type', 'client');
  form.append('message', msg);

  if (fileEl.files.length){
    form.append('file', fileEl.files[0]);
  }

  fetch('save_chat.php', { method:'POST', body:form })
    .then(() => {
      inputEl.value = '';
      fileEl.value = '';
      loadChat();
    });
}

inputEl.addEventListener('keydown', e=>{
  if (e.key === 'Enter' && !e.shiftKey){
    e.preventDefault();
    sendMessage();
  }
});

fileEl.addEventListener('change', ()=> sendMessage());

setInterval(loadChat, 1200);
window.addEventListener('load', loadChat);
</script>

</body>
</html>
