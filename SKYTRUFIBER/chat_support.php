<?php
include '../db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

$username = $_GET['client'] ?? 'Guest'; // REQUIRED matching ?client=Alex

date_default_timezone_set("Asia/Manila");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SkyTruFiber — Support Chat</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
  --header:#0084FF;--outgoing:#0084FF;--incoming:#f0f0f0;
  --text-on-blue:#fff;--text-on-gray:#0a0a0a;
}
body{margin:0;display:flex;justify-content:center;align-items:center;height:100vh;
background:#cfe5ff;font-family:'Segoe UI',sans-serif;}
.chat-wrap{width:430px;height:92vh;background:#fff;border-radius:18px;
display:flex;flex-direction:column;box-shadow:0 10px 30px rgba(0,0,0,.15);}
.chat-header{padding:12px;display:flex;align-items:center;gap:10px;background:var(--header);color:#fff;}
.chat-header img{width:36px;height:36px}
.messages{flex:1;overflow:auto;padding:14px;background:#f9fbff;}
.msg-row{margin:8px 0;display:flex;gap:8px;align-items:flex-end}
.msg-in .bubble{background:var(--incoming);color:var(--text-on-gray)}
.msg-out{justify-content:flex-end}
.msg-out .bubble{background:var(--outgoing);color:var(--text-on-blue)}
.avatar{width:28px;height:28px;background:#ddd;border-radius:50%;
display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700}
.bubble{max-width:70%;padding:10px 12px;border-radius:18px;font-size:14px;white-space:pre-wrap}
.time{font-size:10px;opacity:.6;margin-top:4px}
.media-img{max-width:200px;border-radius:12px;margin-top:6px}
.media-video{max-width:230px;border-radius:12px;margin-top:6px}
.input-bar{display:flex;gap:6px;padding:10px;border-top:1px solid #ddd}
.input-bar input{flex:1;padding:12px;border-radius:999px;border:1px solid #ddd}
button{padding:10px 16px;border:none;border-radius:14px;background:var(--outgoing);color:#fff;cursor:pointer}
button:hover{opacity:.85}
#previewModal{position:fixed;inset:0;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center;z-index:9999}
#previewBox{background:#fff;padding:14px;border-radius:14px;text-align:center;width:90%;max-width:350px}
</style>
</head>
<body>

<div class="chat-wrap">
  <div class="chat-header">
    <img src="../SKYTRUFIBER.png">
    <div>
      <div style="font-size:16px;font-weight:700;">SkyTruFiber Support</div>
      <div style="font-size:12px;">Connected</div>
    </div>
  </div>

  <div id="chatBox" class="messages"></div>

  <div class="input-bar">
    <input id="message" placeholder="Write a message…" autocomplete="off">
    <input type="file" id="fileUpload" accept="image/*,video/*" style="display:none">
    <button onclick="fileUpload.click()">📎</button>
    <button onclick="sendMessage()">Send</button>
  </div>
</div>

<div id="previewModal">
  <div id="previewBox">
    <h3>Send this media?</h3>
    <div id="previewContent"></div>
    <button onclick="confirmSendMedia(true)">Send</button>
    <button onclick="confirmSendMedia(false)" style="background:#aaa;color:#000">Cancel</button>
  </div>
</div>

<script>
let USERNAME = <?= json_encode($username) ?>;
let clientID = null;
let selectedFile = null;

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
      img.className="media-img"; img.src="../"+m.media_path;
      bubble.append(img);
    } else {
      const v=document.createElement("video");
      v.className="media-video"; v.controls=true; v.src="../"+m.media_path;
      bubble.append(v);
    }
  }

  const time=document.createElement("div");
  time.className="time"; time.textContent=m.created_at;
  bubble.append(time);

  row.append(av); row.append(bubble);
  document.getElementById("chatBox").append(row);
}

function loadChat(){
  const url = clientID ? `load_chat.php?client_id=${clientID}` :
                         `load_chat.php?client=${encodeURIComponent(USERNAME)}`;

  fetch(url).then(r=>r.json()).then(list=>{
      document.getElementById("chatBox").innerHTML="";
      list.forEach(renderRow);
      clientID = list[0]?.client_id ?? clientID;
      chatBox.scrollTop = chatBox.scrollHeight;
  });
}

function sendMessage(){
  const msg = document.getElementById("message").value.trim();
  if(!msg && !selectedFile) return;

  const form = new FormData();
  form.append("sender_type","client");
  form.append("message",msg);
  form.append("username",USERNAME);
  if(selectedFile) form.append("file",selectedFile);

  fetch("save_chat.php",{method:"POST",body:form})
  .then(r=>r.json()).then(res=>{
      if(res.client_id) clientID = res.client_id;
      document.getElementById("message").value="";
      selectedFile=null; fileUpload.value="";
      loadChat();
  });
}

fileUpload.addEventListener("change",()=>{
  selectedFile=fileUpload.files[0];
  const ext = selectedFile.name.split('.').pop().toLowerCase();
  const prev=document.getElementById("previewContent");
  prev.innerHTML="";

  if(["jpg","jpeg","png","gif","webp"].includes(ext)){
    const img=document.createElement("img");
    img.src=URL.createObjectURL(selectedFile); img.style.maxWidth="100%";
    prev.append(img);
  } else {
    const v=document.createElement("video");
    v.src=URL.createObjectURL(selectedFile); v.controls=true; v.style.maxWidth="100%";
    prev.append(v);
  }

  document.getElementById("previewModal").style.display="flex";
});

function confirmSendMedia(ok){
  document.getElementById("previewModal").style.display="none";
  if(ok) sendMessage(); else {selectedFile=null;fileUpload.value="";}
}

document.getElementById("message").addEventListener("keydown",e=>{
  if(e.key==="Enter"){e.preventDefault();sendMessage();}
});

setInterval(loadChat,1000);
loadChat();
</script>

</body>
</html>
