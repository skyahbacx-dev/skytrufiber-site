<?php
include '../db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

$username = $_GET['username'] ?? 'Guest';
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

/* ------ Messenger UI Styling (same design) ------ */

:root{
  --header:#0084FF;
  --outgoing:#0084FF;
  --incoming:#f0f0f0;
  --text-on-blue:#fff;
  --text-on-gray:#0a0a0a;
  --border:1px solid rgba(0,0,0,.06);
  --shadow:0 10px 28px rgba(0,0,0,.18);
}

body{
  margin:0; display:flex; justify-content:center; align-items:center;
  height:100vh; background:linear-gradient(135deg,#c2e1ff,#f3f8ff);
  font-family:'Segoe UI',sans-serif;
}

.chat-wrap{
  width:min(430px,95vw); height:min(92vh,800px);
  background:#fff; border-radius:18px; box-shadow:var(--shadow);
  display:flex; flex-direction:column; overflow:hidden; border:var(--border);
}

.chat-header{
  padding:12px; display:flex; align-items:center; gap:10px;
  background:var(--header); color:#fff;
}
.logo img{width:34px}
.head-title{font-size:16px;font-weight:700}
.head-sub{font-size:12px;opacity:.9}

.messages{
  flex:1; overflow:auto; padding:14px;
  background:#f9fbff;
}

.msg-row{margin:8px 0; display:flex; gap:8px; align-items:flex-end}
.msg-in .bubble{background:var(--incoming);color:var(--text-on-gray);border-top-left-radius:6px}
.msg-out{justify-content:flex-end}
.msg-out .bubble{background:var(--outgoing);color:var(--text-on-blue);border-top-right-radius:6px}

.avatar{
  width:30px; height:30px; background:#ddd; border-radius:50%;
  display:flex; justify-content:center; align-items:center; font-weight:700
}

.bubble{
  max-width:70%; padding:10px 12px; border-radius:18px;
  box-shadow:0 3px 8px rgba(0,0,0,.06); font-size:14px; white-space:pre-wrap
}

.time{font-size:10px; margin-top:5px; opacity:.6}

.media-img{
  max-width:200px; border-radius:12px; margin-top:6px;
}
.media-video{
  max-width:240px; border-radius:12px; margin-top:6px;
}

.typing{display:none; padding-left:40px; font-size:12px; color:#555}
.dot{width:6px;height:6px;background:#777;border-radius:50%;display:inline-block;margin:0 1px;animation:blink 1.2s infinite}
@keyframes blink{50%{opacity:.1}}

.input-bar{
  display:flex; gap:6px; padding:10px; border-top:var(--border);
}
.input-bar input{
  flex:1; padding:12px; border:1px solid #ddd; border-radius:999px; font-size:14px;
}
button{border:none;padding:10px 14px;border-radius:14px;background:var(--outgoing);color:#fff;cursor:pointer}
button:hover{background:#0073db}
</style>
</head>
<body>

<div class="chat-wrap">
  <div class="chat-header">
    <div class="logo"><img src="../SKYTRUFIBER.png"></div>
    <div>
      <div class="head-title">SkyTruFiber Support</div>
      <div class="head-sub">Support Team Active</div>
    </div>
  </div>

  <div id="chatBox" class="messages"></div>
  <div id="typing" class="typing">Typing <span class="dot"></span><span class="dot"></span><span class="dot"></span></div>

  <div class="input-bar">
    <input id="message" placeholder="Write a message…" autocomplete="off">
    <input type="file" id="fileUpload" accept="image/*,video/*" style="display:none;">
    <button onclick="document.getElementById('fileUpload').click()">📎</button>
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
const USERNAME = <?= json_encode($username) ?>;
const chatBox  = document.getElementById('chatBox');
const inputEl  = document.getElementById('message');
const fileEl   = document.getElementById('fileUpload');

function renderRow(m){
  const isCSR = m.sender_type === 'csr';
  const row = document.createElement('div');
  row.className = isCSR ? 'msg-row msg-in' : 'msg-row msg-out';

  const av = document.createElement('div');
  av.className='avatar';
  av.textContent = isCSR ? "C" : USERNAME.charAt(0).toUpperCase();

  const bubble = document.createElement('div');
  bubble.className='bubble';

  if(m.message) bubble.appendChild(document.createTextNode(m.message));

  if(m.media_path){
    if(m.media_type === 'image'){
      const img = document.createElement('img');
      img.src = m.media_path;
      img.className='media-img';
      bubble.appendChild(img);
    }
    if(m.media_type === 'video'){
      const vid = document.createElement('video');
      vid.src = m.media_path; vid.controls=true;
      vid.className='media-video';
      bubble.appendChild(vid);
    }
  }

  const t = document.createElement('div');
  t.className='time';
  t.textContent = m.created_at;
  bubble.appendChild(t);

  row.appendChild(av);
  row.appendChild(bubble);
  chatBox.appendChild(row);
}

function loadChat(){
  fetch('load_chat.php?client=' + encodeURIComponent(USERNAME))
    .then(r=>r.json())
    .then(list=>{
      chatBox.innerHTML='';
      list.forEach(renderRow);
      chatBox.scrollTop = chatBox.scrollHeight;
    });
}

function sendMessage(){
  const msg = inputEl.value.trim();
  if(!msg && !fileEl.files.length) return;

  const form = new FormData();
  form.append('sender_type','client');
  form.append('message',msg);
  form.append('username',USERNAME);

  if(fileEl.files.length){
    form.append('file',fileEl.files[0]);
  }

  fetch('save_chat.php',{method:'POST',body:form})
    .then(()=>{ inputEl.value=''; fileEl.value=''; loadChat(); });
}

inputEl.addEventListener('keydown', e=>{
  if(e.key === 'Enter' && !e.shiftKey){
    e.preventDefault(); sendMessage();
  }
});

fileEl.addEventListener('change', sendMessage);

setInterval(loadChat,1000);
loadChat();
</script>

</body>
</html>
