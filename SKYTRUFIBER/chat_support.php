<?php
include '../db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

$username = $_GET['client'] ?? $_GET['username'] ?? 'Guest';

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
  margin:0;display:flex;justify-content:center;align-items:center;height:100vh;
  background:linear-gradient(135deg,#c2e1ff,#f3f8ff);font-family:'Segoe UI',sans-serif;
}

.chat-wrap{
  width:min(430px,95vw);height:min(92vh,800px);background:#fff;border-radius:18px;
  display:flex;flex-direction:column;box-shadow:var(--shadow);overflow:hidden;border:var(--border);
}

.chat-header{
  padding:12px;display:flex;align-items:center;gap:10px;background:var(--header);color:#fff;
}
.chat-header img{width:34px;height:34px}

.head-title{font-size:16px;font-weight:700}
.head-sub{font-size:12px;opacity:.9}

.messages{
  flex:1;overflow:auto;padding:14px;background:#f9fbff;
}

.msg-row{margin:8px 0;display:flex;gap:8px;align-items:flex-end}
.msg-in .bubble{background:var(--incoming);color:var(--text-on-gray);border-top-left-radius:6px}
.msg-out{justify-content:flex-end}
.msg-out .bubble{background:var(--outgoing);color:var(--text-on-blue);border-top-right-radius:6px}

.avatar{width:30px;height:30px;background:#ddd;border-radius:50%;display:flex;
  justify-content:center;align-items:center;font-size:14px;font-weight:700}

.bubble{max-width:70%;padding:10px 12px;border-radius:18px;box-shadow:0 3px 8px rgba(0,0,0,.06);
  font-size:14px;white-space:pre-wrap}

.time{font-size:10px;margin-top:5px;opacity:.6}

.media-img{max-width:200px;border-radius:12px;margin-top:6px}
.media-video{max-width:240px;border-radius:12px;margin-top:6px}

.input-bar{
  display:flex;gap:6px;padding:10px;border-top:var(--border);
}
.input-bar input{
  flex:1;padding:12px;border:1px solid #ddd;border-radius:999px;font-size:14px;
}
button{border:none;padding:10px 14px;border-radius:14px;background:var(--outgoing);color:#fff;cursor:pointer}
button:hover{background:#0073db}

/* PREVIEW MODAL */
#previewModal{
  position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;
  align-items:center;justify-content:center;z-index:9999;
}
#previewBox{
  background:#fff;padding:16px;border-radius:14px;width:90%;max-width:330px;text-align:center;
}
</style>
</head>

<body>

<div class="chat-wrap">

  <div class="chat-header">
    <img src="../SKYTRUFIBER.png">
    <div>
      <div class="head-title">SkyTruFiber Support</div>
      <div class="head-sub">Support Team Active</div>
    </div>
  </div>

  <div id="chatBox" class="messages"></div>

  <div class="input-bar">
    <input id="message" placeholder="Write a message…" autocomplete="off"/>
    <input type="file" id="fileUpload" accept="image/*,video/*" style="display:none;">
    <button onclick="document.getElementById('fileUpload').click()">📎</button>
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<!-- PREVIEW MODAL -->
<div id="previewModal">
  <div id="previewBox">
    <h3 style="margin-bottom:10px;">Send this media?</h3>
    <div id="previewContent" style="margin-bottom:12px;"></div>
    <button onclick="confirmSendMedia(true)">Send</button>
    <button onclick="confirmSendMedia(false)" style="background:#bbb;color:#000;">Cancel</button>
  </div>
</div>

<script>
const USERNAME = <?= json_encode($username) ?>;
const chatBox  = document.getElementById('chatBox');
const inputEl  = document.getElementById('message');
const fileEl   = document.getElementById('fileUpload');

let selectedFile = null;

function relativeTime(dateStr){
  return dateStr; // already formatted in PHP
}

/* RENDER MESSAGE */
function renderRow(m){
  const row=document.createElement('div');
  const isCSR=(m.sender_type === 'csr');
  row.className=isCSR?'msg-row msg-in':'msg-row msg-out';

  const av=document.createElement('div');
  av.className='avatar';
  av.textContent=isCSR?'C':USERNAME.charAt(0).toUpperCase();

  const bubble=document.createElement('div');
  bubble.className='bubble';

  if(m.message) bubble.appendChild(document.createTextNode(m.message));

  if(m.media_path){
    if(m.media_type==='image'){
      const img=document.createElement('img');
      img.src="../"+m.media_path;
      img.className='media-img';
      bubble.appendChild(img);
    } else {
      const vid=document.createElement('video');
      vid.src="../"+m.media_path;
      vid.controls=true; vid.className='media-video';
      bubble.appendChild(vid);
    }
  }

  const t=document.createElement('div');
  t.className='time'; t.textContent=m.created_at;
  bubble.appendChild(t);

  row.appendChild(av);
  row.appendChild(bubble);

  chatBox.appendChild(row);
}

/* LOAD CHAT */
function loadChat(){
  fetch('load_chat.php?client=' + encodeURIComponent(USERNAME))
  .then(r=>r.json())
  .then(list=>{
      chatBox.innerHTML='';
      list.forEach(renderRow);
      chatBox.scrollTop=chatBox.scrollHeight;
  });
}

/* SEND MESSAGE */
function sendMessage(){
  const msg=inputEl.value.trim();
  if(!msg && !selectedFile) return;

  const form=new FormData();
  form.append('sender_type','client');
  form.append('message',msg);
  form.append('username',USERNAME);
  if(selectedFile) form.append('file',selectedFile);

  fetch('save_chat.php',{method:'POST',body:form})
  .then(()=>{inputEl.value='';selectedFile=null;fileEl.value='';loadChat();});
}

/* File Preview */
fileEl.addEventListener('change', ()=>{
  if(!fileEl.files.length) return;
  selectedFile=fileEl.files[0];

  const ext=selectedFile.name.split('.').pop().toLowerCase();
  const preview=document.getElementById("previewContent");
  preview.innerHTML="";

  if(['jpg','jpeg','png','gif','webp'].includes(ext)){
    const img=document.createElement('img');
    img.src=URL.createObjectURL(selectedFile);
    img.style.maxWidth="100%"; img.style.borderRadius="10px";
    preview.appendChild(img);
  } else {
    const video=document.createElement('video');
    video.src=URL.createObjectURL(selectedFile);
    video.controls=true; video.style.maxWidth="100%";
    preview.appendChild(video);
  }

  document.getElementById("previewModal").style.display="flex";
});

function confirmSendMedia(ok){
  document.getElementById("previewModal").style.display="none";
  if(!ok){ selectedFile=null;fileEl.value="";return; }
  sendMessage();
}

inputEl.addEventListener('keydown', e=>{
  if(e.key==="Enter"){e.preventDefault();sendMessage();}
});

setInterval(loadChat,1000);
loadChat();
</script>

</body>
</html>
