<?php
// chat_support.php (Messenger style)
include '../db_connect.php';
header('Content-Type: text/html; charset=UTF-8');

$username = $_GET['username'] ?? 'Guest';

/* =======================
   FETCH LAST ASSIGNED CSR
   ======================= */
$csr_name_display = 'CSR Support Team';
try {
    $stmt = $conn->prepare("
        SELECT assigned_csr, csr_fullname
        FROM chat
        WHERE client_id = (
            SELECT id FROM clients WHERE username = :u LIMIT 1
        )
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([':u' => $username]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['csr_fullname'])) {
            $csr_name_display = $row['csr_fullname'];
        }
    }
} catch (Throwable $e) {
    // leave default label
}

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>SkyTruFiber — Support Chat</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
/* ====== Messenger-style UI ====== */

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

*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;
  font-family: "Segoe UI", Arial, sans-serif;
  background: linear-gradient(135deg,#c2e1ff,#f3f8ff);
  display:flex;
  align-items:center;
  justify-content:center;
  overflow:hidden;
  position:relative;
}
body::before{
  content:"";
  position:absolute; inset:0;
  background:url('../SKYTRUFIBER.png') center/420px no-repeat;
  opacity:.08; filter:grayscale(100%);
  pointer-events:none;
}

/* Shell */
.chat-wrap{
  width:min(430px,95vw);
  height:min(92vh,800px);
  background:#fff;
  border-radius:18px;
  box-shadow:var(--shadow);
  display:flex; flex-direction:column;
  overflow:hidden;
  border:var(--border);
}

/* Header */
.chat-header{
  background:var(--header);
  color:#fff;
  padding:12px 14px;
  display:flex; align-items:center; gap:10px;
}
.logo{
  width:38px; height:38px; border-radius:50%;
  background:#fff; display:flex; align-items:center; justify-content:center;
  overflow:hidden;
}
.logo img{ width:34px; height:34px; object-fit:contain; }
.head-text{
  display:flex; flex-direction:column; line-height:1.15;
}
.head-title{ font-weight:700; font-size:15.5px; }
.head-sub{ font-size:12.5px; opacity:.9 }

/* Messages area */
.messages{
  flex:1; overflow:auto; padding:16px 12px;
  background:#f9fbff;
}
.msg-row{
  display:flex; gap:8px; margin:8px 0; align-items:flex-end;
}
.msg-in .avatar{ order:1 }
.msg-in .bubble{ order:2 }
.msg-out{ justify-content:flex-end }
.msg-out .avatar{ order:2 }
.msg-out .bubble{ order:1 }

/* Avatar circles */
.avatar{
  width:32px; height:32px; border-radius:50%;
  background:#e6e6e6; color:#333;
  display:flex; align-items:center; justify-content:center;
  font-weight:700; font-size:13px;
  user-select:none;
  overflow:hidden;
}
.avatar img{ width:100%; height:100%; object-fit:cover }

/* Bubbles */
.bubble{
  max-width:75%;
  padding:10px 12px;
  border-radius:18px;
  box-shadow:0 3px 8px rgba(0,0,0,.06);
  word-wrap:break-word; white-space:pre-wrap;
  font-size:14.3px; line-height:1.38;
}
.msg-in .bubble{
  background:var(--incoming); color:var(--text-on-gray);
  border-top-left-radius:6px;
}
.msg-out .bubble{
  background:var(--outgoing); color:var(--text-on-blue);
  border-top-right-radius:6px;
}

/* Timestamp */
.time{
  font-size:11px; opacity:.6; margin-top:4px;
}

/* Typing indicator (client-side only) */
.typing{
  display:none; gap:6px; align-items:center; margin:8px 0 4px 40px;
  color:#555; font-size:12.5px;
}
.dot{
  width:6px; height:6px; border-radius:50%;
  background:#999; opacity:.6; animation: blink 1.2s infinite ease-in-out;
}
.dot:nth-child(2){ animation-delay:.2s }
.dot:nth-child(3){ animation-delay:.4s }
@keyframes blink{
  0%,80%,100%{opacity:.3; transform:translateY(0)}
  40%{opacity:.9; transform:translateY(-2px)}
}

/* Input */
.input-bar{
  display:flex; gap:8px; padding:10px; border-top:var(--border);
  background:#fff;
}
.input-bar input{
  flex:1; padding:12px 14px; border-radius:999px; border:1px solid #dcdcdc; font-size:14px;
}
.send-btn{
  border:none; background:var(--outgoing); color:#fff; font-weight:700;
  padding:12px 16px; border-radius:999px; cursor:pointer;
}
.send-btn:hover{ background:#0073db }

/* Scrollbar (webkit) */
.messages::-webkit-scrollbar{ width:8px }
.messages::-webkit-scrollbar-thumb{
  background:#c9dff0; border-radius:8px;
}

@media (max-width:420px){
  .chat-wrap{ height:94vh }
}
</style>
</head>
<body>

<div class="chat-wrap">
  <!-- Header -->
  <div class="chat-header">
    <div class="logo">
      <img src="../SKYTRUFIBER.png" alt="Logo">
    </div>
    <div class="head-text">
      <div class="head-title">SkyTruFiber Support</div>
      <div class="head-sub">Connected to <?= e($csr_name_display) ?></div>
    </div>
  </div>

  <!-- Messages -->
  <div id="chatBox" class="messages"></div>

  <!-- Typing indicator (shown while user typing) -->
  <div id="typing" class="typing">
    <span>Typing</span>
    <div class="dot"></div><div class="dot"></div><div class="dot"></div>
  </div>

  <!-- Input -->
  <div class="input-bar">
    <input id="message" type="text" placeholder="Write a message…" autocomplete="off" />
    <button class="send-btn" onclick="sendMessage()">Send</button>
  </div>
</div>

<script>
/* ===== Client side logic (keep your endpoints) ===== */

const USERNAME = <?= json_encode($username, JSON_UNESCAPED_UNICODE) ?>;
const chatBox  = document.getElementById('chatBox');
const typingEl = document.getElementById('typing');
const inputEl  = document.getElementById('message');

let typingTimer;

/* Render one message row */
function renderRow(m){
  const isCSR = m.sender_type === 'csr';
  const row = document.createElement('div');
  row.className = isCSR ? 'msg-row msg-in' : 'msg-row msg-out';

  // avatar
  const av = document.createElement('div');
  av.className = 'avatar';
  if (isCSR){
    // CSR avatar - show initials C
    av.textContent = 'C';
  } else {
    // user avatar - first initial
    av.textContent = (USERNAME || 'U').trim().charAt(0).toUpperCase() || 'U';
  }

  // bubble
  const bubble = document.createElement('div');
  bubble.className = 'bubble';
  bubble.textContent = m.message || '';

  // time
  const t = document.createElement('div');
  t.className = 'time';
  const dt = new Date(m.created_at || Date.now());
  t.textContent = dt.toLocaleString();
  bubble.appendChild(t);

  // assemble
  row.appendChild(av);
  row.appendChild(bubble);
  chatBox.appendChild(row);
}

/* Load messages */
function loadChat(){
  fetch('load_chat.php?client=' + encodeURIComponent(USERNAME))
    .then(r => r.json())
    .then(list => {
      chatBox.innerHTML = '';
      list.forEach(renderRow);
      chatBox.scrollTop = chatBox.scrollHeight;
    })
    .catch(()=>{ /* ignore */ });
}

/* Send message */
function sendMessage(){
  const text = (inputEl.value || '').trim();
  if (!text) return;

  fetch('save_chat.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
      sender_type: 'client',
      message: text,
      client: USERNAME
    })
  }).then(() => {
    inputEl.value = '';
    hideTyping();
    loadChat();
  });
}

/* Typing indicator (client-side only) */
function showTyping(){
  typingEl.style.display = 'flex';
  if (typingTimer) clearTimeout(typingTimer);
  typingTimer = setTimeout(hideTyping, 1200);
}
function hideTyping(){
  typingEl.style.display = 'none';
}
inputEl.addEventListener('input', showTyping);

/* Poll messages */
setInterval(loadChat, 1500);
window.addEventListener('load', loadChat);
</script>

</body>
</html>
