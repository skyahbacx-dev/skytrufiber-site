<?php
include '../db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

$username = $_GET['client'] ?? 'Guest';
date_default_timezone_set("Asia/Manila");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber — Support Chat</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{margin:0;display:flex;justify-content:center;align-items:center;height:100vh;background:#dceeff;font-family:'Segoe UI',sans-serif}
.chat-wrap{width:min(430px,95vw);height:min(92vh,800px);background:#fff;border-radius:18px;display:flex;flex-direction:column;box-shadow:0 10px 30px rgba(0,0,0,.2);overflow:hidden}
.chat-header{padding:12px;background:#0084FF;color:#fff;display:flex;align-items:center;gap:10px}
.chat-header img{width:30px}
.messages{flex:1;overflow:auto;padding:14px;background:#f9fbff}
.msg-row{display:flex;margin:8px 0;gap:8px}
.msg-out{justify-content:flex-end}
.bubble{padding:10px 12px;border-radius:18px;max-width:70%;font-size:14px}
.msg-in .bubble{background:#ececec;border-top-left-radius:6px}
.msg-out .bubble{background:#0084FF;color:#fff;border-top-right-radius:6px}
.avatar{width:28px;height:28px;border-radius:50%;background:#ccc;display:flex;justify-content:center;align-items:center;font-weight:700}
.time{font-size:10px;margin-top:5px;opacity:.6}
.media-img{max-width:200px;margin-top:6px;border-radius:12px}
.media-video{max-width:240px;margin-top:6px;border-radius:12px}
.input-bar{display:flex;gap:6px;padding:10px;border-top:1px solid #ccc}
.input-bar input{flex:1;padding:10px;border-radius:999px;border:1px solid #ddd}
button{border:none;padding:10px 14px;background:#0084FF;color:#fff;border-radius:14px;cursor:pointer}
</style>
</head>

<body>

<div class="chat-wrap">
  <div class="chat-header">
    <img src="../SKYTRUFIBER.png">
    <div>
      <b>SkyTruFiber Support</b><br>
      <span style="font-size:11px;opacity:.9;">Connected</span>
    </div>
  </div>

  <div id="chatBox" class="messages"></div>

  <div class="input-bar">
    <input id="message" placeholder="Write a message…" autocomplete="off">
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
const USERNAME = <?= json_encode($username) ?>;
const chatBox = document.getElementById("chatBox");
const messageInput = document.getElementById("message");

function renderRow(m){
  const row=document.createElement("div");
  const isCSR = (m.sender_type === "csr");
  row.className = isCSR ? "msg-row msg-in" : "msg-row msg-out";

  const av=document.createElement("div");
  av.className="avatar";
  av.textContent = isCSR ? "C" : USERNAME.charAt(0).toUpperCase();

  const bubble=document.createElement("div");
  bubble.className="bubble";
  bubble.textContent = m.message;

  const t=document.createElement("div");
  t.className="time";
  t.textContent = m.created_at;
  bubble.appendChild(document.createElement("br"));
  bubble.appendChild(t);

  row.appendChild(av);
  row.appendChild(bubble);
  chatBox.appendChild(row);
}

function loadChat(){
  fetch("load_chat.php?client=" + encodeURIComponent(USERNAME))
  .then(r=>r.json()).then(list=>{
    chatBox.innerHTML="";
    list.forEach(renderRow);
    chatBox.scrollTop = chatBox.scrollHeight;
  });
}

function sendMessage(){
  const msg = messageInput.value.trim();
  if(!msg) return;

  const form = new FormData();
  form.append("sender_type","client");
  form.append("message",msg);
  form.append("username",USERNAME);

  fetch("save_chat.php",{method:"POST",body:form}).then(()=>{
    messageInput.value="";
    loadChat();
  });
}

setInterval(loadChat, 1000);
loadChat();
</script>

</body>
</html>
