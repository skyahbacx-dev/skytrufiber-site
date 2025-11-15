<?php
include '../db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

$username = $_GET['username'] ?? $_GET['client'] ?? 'Guest';
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
.chat-wrap{width:min(430px,95vw);height:min(92vh,800px);background:#fff;border-radius:18px;display:flex;flex-direction:column;box-shadow:0 10px 28px rgba(0,0,0,.2);overflow:hidden}
.chat-header{padding:12px;background:#0084FF;color:#fff;display:flex;align-items:center;gap:10px}
.chat-header img{width:34px}
.messages{flex:1;overflow:auto;padding:14px;background:#f9fbff}
.msg-row{display:flex;margin:8px 0;gap:8px}
.msg-out{justify-content:flex-end}
.bubble{padding:10px 12px;border-radius:18px;max-width:70%;font-size:14px;white-space:pre-wrap}
.msg-in .bubble{background:#ececec;border-top-left-radius:6px;color:#000}
.msg-out .bubble{background:#0084FF;color:#fff;border-top-right-radius:6px}
.avatar{width:28px;height:28px;background:#bbb;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700}
.time{font-size:10px;margin-top:4px;opacity:.6}
.media-img{max-width:200px;margin-top:6px;border-radius:10px}
.media-video{max-width:240px;margin-top:6px;border-radius:10px}
.input-bar{display:flex;gap:6px;padding:10px;border-top:1px solid #ddd}
.input-bar input{flex:1;padding:10px;border-radius:999px;border:1px solid #ccc}
button{border:none;padding:10px 14px;border-radius:14px;background:#0084FF;color:#fff;cursor:pointer}
#fileUpload{display:none}
</style>
</head>

<body>
<div class="chat-wrap">
  <div class="chat-header">
    <img src="../SKYTRUFIBER.png">
    <div><b>SkyTruFiber Support</b><br><span style="font-size:11px;">Support Team Active</span></div>
  </div>

  <div id="chatBox" class="messages"></div>

  <div class="input-bar">
    <input id="message" placeholder="Write a message…" autocomplete="off">
    <input type="file" id="fileUpload" accept="image/*,video/*">
    <button onclick="document.getElementById('fileUpload').click()">📎</button>
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
const USERNAME = <?= json_encode($username) ?>;
const chatBox = document.getElementById("chatBox");
const messageInput = document.getElementById("message");
const fileInput = document.getElementById("fileUpload");
let selectedFile = null;

fileInput.addEventListener("change",()=>{ selectedFile = fileInput.files[0]; sendMessage(); });

function renderRow(m){
  const row=document.createElement("div");
  const isCSR=(m.sender_type==="csr");
  row.className=isCSR?"msg-row msg-in":"msg-row msg-out";

  const av=document.createElement("div");
  av.className="avatar";
  av.textContent=isCSR?"C":USERNAME.charAt(0).toUpperCase();

  const bubble=document.createElement("div");
  bubble.className="bubble";

  if(m.message) bubble.append(m.message);

  if(m.media_path){
    if(m.media_type==="image"){
      const img=document.createElement("img");
      img.src="../"+m.media_path; img.className="media-img";
      bubble.append(img);
    } else {
      const v=document.createElement("video");
      v.src="../"+m.media_path; v.controls=true; v.className="media-video";
      bubble.append(v);
    }
  }

  const t=document.createElement("div");
  t.className="time";
  t.textContent=m.created_at;
  bubble.append(document.createElement("br"));
  bubble.append(t);

  row.append(av); row.append(bubble);
  chatBox.append(row);
}

function loadChat(){
  fetch("load_chat_client.php?username="+encodeURIComponent(USERNAME))
  .then(r=>r.json()).then(list=>{
    chatBox.innerHTML="";
    list.forEach(renderRow);
    chatBox.scrollTop = chatBox.scrollHeight;
  });
}

function sendMessage(){
  const msg = messageInput.value.trim();
  if(!msg && !selectedFile) return;

  const form=new FormData();
  form.append("sender_type","client");
  form.append("message",msg);
  form.append("username",USERNAME);
  if(selectedFile) form.append("file",selectedFile);

  fetch("save_chat_client.php",{method:"POST",body:form}).then(()=>{
    messageInput.value="";
    selectedFile=null;
    fileInput.value="";
    loadChat();
  });
}

messageInput.addEventListener("keydown",e=>{
  if(e.key==="Enter" && !e.shiftKey){e.preventDefault(); sendMessage();}
});

setInterval(loadChat, 1000);
loadChat();
</script>
</body>
</html>
