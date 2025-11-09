<?php
session_start();
include '../db_connect.php';

// --- Guard ---
if (!isset($_SESSION['csr_user'])) {
  header("Location: csr_login.php");
  exit;
}
$csr_user = $_SESSION['csr_user'];

// --- Who am I? ---
$stmt = $conn->prepare("SELECT full_name, is_online FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

// Logo fallback
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* =========================================================
   AJAX ENDPOINTS (same URL: csr_dashboard.php?ajax=...)
   ========================================================= */
if (isset($_GET['ajax'])) {

  // -------- Load clients list (all / mine) --------
  if ($_GET['ajax'] === 'clients') {
    $tab = $_GET['tab'] ?? 'all';

    if ($tab === 'mine') {
      $stmt = $conn->prepare("
        SELECT c.id, c.name, c.assigned_csr,
               MAX(ch.created_at) AS last_chat
        FROM clients c
        LEFT JOIN chat ch ON ch.client_id = c.id
        WHERE c.assigned_csr = :csr
        GROUP BY c.id, c.name, c.assigned_csr
        ORDER BY last_chat DESC NULLS LAST, c.name ASC
      ");
      $stmt->execute([':csr' => $csr_user]);
    } else {
      $stmt = $conn->query("
        SELECT c.id, c.name, c.assigned_csr,
               MAX(ch.created_at) AS last_chat
        FROM clients c
        LEFT JOIN chat ch ON ch.client_id = c.id
        GROUP BY c.id, c.name, c.assigned_csr
        ORDER BY last_chat DESC NULLS LAST, c.name ASC
      ");
    }

    $html = '';
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $assigned = $r['assigned_csr'] ?: 'Unassigned';
      $owned = ($assigned === $csr_user);
      $canAssign = ($assigned === 'Unassigned');
      $badge = $assigned === 'Unassigned'
        ? "<span class='badge warn'>Unassigned</span>"
        : ($owned ? "<span class='badge mine'>You</span>" : "<span class='badge other'>$assigned</span>");

      $btn = $canAssign
        ? "<button class='icon-btn assign' title='Assign to me' onclick='assignClient({$r['id']})'>＋</button>"
        : ($owned
            ? "<button class='icon-btn unassign' title='Unassign' onclick='unassignClient({$r['id']})'>−</button>"
            : "<button class='icon-btn lock' title='Owned by $assigned' disabled>🔒</button>");

      $html .= "
        <div class='client-item' data-id='{$r['id']}' data-name=\"".htmlspecialchars($r['name'],ENT_QUOTES)."\" data-csr=\"".htmlspecialchars($assigned,ENT_QUOTES)."\">
          <div class='client-ava'>👤</div>
          <div class='client-meta'>
            <div class='title'>".htmlspecialchars($r['name'])."</div>
            <div class='sub'>Assigned: $badge</div>
          </div>
          <div class='client-actions'>$btn</div>
        </div>
      ";
    }
    echo $html; exit;
  }

  // -------- Assign / Unassign --------
  if ($_GET['ajax'] === 'assign' && !empty($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];
    $chk = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
    $chk->execute([':id'=>$id]);
    $cr = $chk->fetch(PDO::FETCH_ASSOC);
    if ($cr && $cr['assigned_csr'] && $cr['assigned_csr'] !== 'Unassigned') { echo 'taken'; exit; }
    $u = $conn->prepare("UPDATE clients SET assigned_csr = :c WHERE id = :id");
    echo $u->execute([':c'=>$csr_user, ':id'=>$id]) ? 'ok' : 'fail'; exit;
  }

  if ($_GET['ajax'] === 'unassign' && !empty($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];
    $u = $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned' WHERE id = :id AND assigned_csr = :csr");
    echo $u->execute([':id'=>$id, ':csr'=>$csr_user]) ? 'ok' : 'fail'; exit;
  }

  // -------- Reminders: load & create --------
  if ($_GET['ajax'] === 'load_reminders') {
    $q = $conn->query("
      SELECT r.id, r.client_id, r.csr_username, r.reminder_type, r.status, r.sent_at,
             c.name AS client_name
      FROM reminders r
      LEFT JOIN clients c ON c.id = r.client_id
      ORDER BY r.id DESC
    ");
    echo json_encode($q->fetchAll(PDO::FETCH_ASSOC)); exit;
  }

  if ($_GET['ajax']==='create_reminder' && !empty($_POST['client_id']) && !empty($_POST['type'])) {
    $cid = (int)$_POST['client_id'];
    $type= $_POST['type']; // 1_WEEK | 3_DAYS
    $ins = $conn->prepare("
      INSERT INTO reminders (client_id, csr_username, reminder_type, status)
      VALUES (:cid, :csr, :type, 'pending')
    ");
    $ins->execute([':cid'=>$cid, ':csr'=>$csr_user, ':type'=>$type]);
    echo 'ok'; exit;
  }

  // -------- Theme persist --------
  if ($_GET['ajax']==='theme' && isset($_POST['theme'])) {
    $_SESSION['theme'] = $_POST['theme'] === 'dark' ? 'dark' : 'light';
    echo 'ok'; exit;
  }

  echo 'noop'; exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>CSR Dashboard — SkyTruFiber</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />

<style>
/* =============================
   THEME & BASE
   ============================= */
:root{
  --bg:#f5fff5;
  --bg-card:#ffffff;
  --ink:#143814;
  --ink-soft:#406c40;
  --brand:#0aa00f;
  --brand-2:#087f0d;
  --accent:#1aa0ff;
  --warn:#ffb302;
  --danger:#e24a4a;
  --muted:#dfe8df;
  --ring:rgba(10,160,15,.35);
  --shadow:0 8px 24px rgba(0,0,0,.08);
}
:root.dark{
  --bg:#0e2010;
  --bg-card:#142415;
  --ink:#dcf1dc;
  --ink-soft:#9ed09e;
  --brand:#10b614;
  --brand-2:#0f9b13;
  --accent:#46b7ff;
  --warn:#ffcc54;
  --danger:#ff6d6d;
  --muted:#203220;
  --ring:rgba(16,182,20,.4);
  --shadow:0 8px 30px rgba(0,0,0,.35);
}
*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;
  font-family: ui-sans-serif,system-ui,Segoe UI,Roboto,Arial;
  background:var(--bg);
  color:var(--ink);
  overflow:hidden;
}

/* =============================
   LAYOUT
   ============================= */
.app{
  display:grid;
  grid-template-columns:260px 1fr;
  grid-template-rows:auto 1fr;
  grid-template-areas:
    "side head"
    "side main";
  height:100vh;
}

/* Header */
.header{
  grid-area:head;
  display:flex;align-items:center;gap:14px;
  padding:10px 18px;
  background:linear-gradient(0deg,var(--brand),var(--brand-2));
  color:#fff; box-shadow:var(--shadow);
  position:relative; z-index:5;
  border-bottom-left-radius:12px;
}
.header .logo{height:40px;opacity:.95}
.header .title{font-weight:800;letter-spacing:.2px}
.header .grow{flex:1}
.header .btn{
  background:rgba(255,255,255,.14);
  color:#fff;border:none;padding:8px 12px;border-radius:10px;
  font-weight:700;cursor:pointer
}
.toggle{
  display:inline-flex;align-items:center;gap:8px;
  padding:6px 10px;border-radius:12px;background:rgba(0,0,0,.2);
  cursor:pointer;user-select:none
}

/* Sidebar */
.sidebar{
  grid-area:side;
  background:linear-gradient(180deg,var(--brand-2),#046904 55%,#035b03);
  color:#fff;
  padding:12px;
  display:flex;flex-direction:column;
  gap:8px; box-shadow:var(--shadow);
  position:relative; z-index:4;
}
.side-head{
  display:flex;align-items:center;gap:10px;
  padding:8px 10px;border-radius:12px;background:rgba(255,255,255,.1)
}
.side-head img{height:22px}
.nav a{
  display:flex;align-items:center; gap:10px;
  padding:12px 10px; color:#fff; text-decoration:none;
  background:rgba(255,255,255,.06); border-radius:10px;
  transition:transform .08s ease, background .2s ease;
}
.nav a.active{background:rgba(255,255,255,.18)}
.nav a:hover{transform:translateY(-1px); background:rgba(255,255,255,.18)}
.nav .ico{width:22px;text-align:center}

/* Main */
.main{
  grid-area:main; display:grid; grid-template-columns:350px 1fr;
  gap:16px; padding:12px 16px 16px 16px;
  overflow:hidden;
}

/* Column: Clients */
.card{
  background:var(--bg-card); border-radius:14px; box-shadow:var(--shadow);
  overflow:hidden; display:flex; flex-direction:column;
  min-height:0;
}
.card .card-head{
  padding:12px 14px; border-bottom:1px solid var(--muted);
  display:flex; gap:10px; align-items:center;
  background:linear-gradient(180deg,rgba(0,0,0,.02),transparent);
}
.search{
  flex:1; display:flex; gap:8px;
  background:var(--bg); border:1px solid var(--muted);
  border-radius:10px; padding:8px 10px; align-items:center;
}
.search input{border:none; background:transparent; color:var(--ink); outline:none; width:100%}

.client-list{overflow:auto; padding:10px}
.client-item{
  display:grid; grid-template-columns:36px 1fr auto; align-items:center;
  gap:10px; padding:10px; border:1px solid var(--muted);
  border-radius:12px; margin-bottom:8px; background:var(--bg-card);
  cursor:pointer; transition: box-shadow .15s ease, transform .06s ease;
}
.client-item:hover{box-shadow:var(--shadow); transform:translateY(-1px)}
.client-item.active{outline:3px solid var(--ring)}
.client-ava{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#b5f0b7,#76e779);display:grid;place-items:center;font-weight:800;color:#0d4c0f}
.client-meta .title{font-weight:800}
.client-meta .sub{font-size:12px;color:var(--ink-soft)}
.badge{padding:.1rem .5rem;border-radius:999px;font-size:12px}
.badge.warn{background:#fff3cd;color:#7d5a00}
.badge.mine{background:#d1ffda;color:#136e18}
.badge.other{background:#cfe7ff;color:#114e94}
.icon-btn{border:none;cursor:pointer;border-radius:10px;padding:6px 8px}
.icon-btn.assign{background:#e8ffe9;color:#0b810f}
.icon-btn.unassign{background:#ffeaea;color:#aa1d1d}
.icon-btn.lock{background:#eee;color:#777}

/* Column: Chat */
.chat{
  min-height:0; display:grid; grid-template-rows:auto 1fr auto; overflow:hidden;
}
.chat-head{
  display:flex; align-items:center; justify-content:space-between;
  padding:12px 14px; border-bottom:1px solid var(--muted);
}
.chat-head .who{font-weight:800}
.chat-body{
  overflow:auto; padding:16px; position:relative;
  background:
    radial-gradient(ellipse at 70% -40%, rgba(10,160,15,.18), transparent 40%),
    radial-gradient(ellipse at -20% 120%, rgba(10,160,15,.08), transparent 45%),
    url('<?= htmlspecialchars($logoPath, ENT_QUOTES) ?>') center center/420px auto no-repeat;
  background-blend-mode:screen;
}
.month-label{
  text-align:center; margin:10px auto; color:var(--ink-soft);
  padding:4px 10px; border-radius:999px; background:var(--bg-card);
  border:1px dashed var(--muted); display:inline-block
}
.msg{max-width:70%; padding:10px 12px; border-radius:12px; margin:6px 0; box-shadow:var(--shadow)}
.msg.client{background:#edfdee}
.msg.csr{background:#dff3ff; margin-left:auto}
.msg .who{font-weight:700; font-size:12px; color:var(--ink-soft)}
.msg .text{white-space:pre-wrap; word-break:break-word; margin-top:3px}
.msg .time{font-size:11px; color:var(--ink-soft); margin-top:4px; text-align:right}

.typing{position:sticky; bottom:8px; display:none; gap:6px; align-items:center; color:var(--ink-soft)}
.typing .dot{width:7px;height:7px;border-radius:50%;background:var(--ink-soft);animation:bounce 1.2s infinite}
.typing .dot:nth-child(2){animation-delay:.2s}
.typing .dot:nth-child(3){animation-delay:.4s}
@keyframes bounce{0%,80%,100%{opacity:.2;transform:translateY(0)}40%{opacity:1;transform:translateY(-4px)}}

.chat-input{
  display:flex; gap:8px; padding:10px; border-top:1px solid var(--muted); background:var(--bg-card)
}
.chat-input input{
  flex:1; border:1px solid var(--muted); border-radius:10px; padding:10px; background:var(--bg);
  color:var(--ink); outline:2px solid transparent
}
.chat-input input:focus{outline-color:var(--ring)}
.chat-input button{
  background:var(--brand); color:#fff; border:none; border-radius:10px; padding:10px 16px; font-weight:800; cursor:pointer
}
.chat-input button:hover{background:var(--brand-2)}

/* Reminders panel */
.rem-head{padding:12px 14px;border-bottom:1px solid var(--muted);display:flex;align-items:center;gap:10px}
.rem-body{padding:10px; overflow:auto}
.table{width:100%; border-collapse:collapse; font-size:14px}
.table th,.table td{border-bottom:1px solid var(--muted); padding:8px 10px; text-align:left}
.group{display:flex; gap:8px; flex-wrap:wrap}
.select,.btn{
  border:1px solid var(--muted); background:var(--bg-card); color:var(--ink);
  padding:8px 10px; border-radius:10px
}
.btn.brand{background:var(--brand); color:#fff; border:none; font-weight:800}
.btn.brand:hover{background:var(--brand-2)}

/* Toast */
.toast{
  position:fixed; right:16px; bottom:16px; background:var(--bg-card);
  color:var(--ink); padding:10px 14px; border-radius:12px; box-shadow:var(--shadow);
  border:1px solid var(--muted); display:none; z-index:100
}

/* Responsive */
@media (max-width: 1100px){
  .main{grid-template-columns:1fr}
  .chat{order:2}
  .card.clients{order:1}
}
</style>
</head>
<body class="<?= isset($_SESSION['theme']) && $_SESSION['theme']==='dark' ? 'dark' : '' ?>">

<div class="app">

  <!-- Header -->
  <div class="header">
    <img src="<?= htmlspecialchars($logoPath) ?>" class="logo" alt="Logo">
    <div class="title">CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></div>
    <div class="grow"></div>

    <div class="toggle" onclick="toggleTheme()">
      <span id="themeLabel">🌞 Light</span>
    </div>

    <a href="csr_logout.php" class="btn">Logout</a>
  </div>

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="side-head"><img src="<?= htmlspecialchars($logoPath) ?>" alt=""><strong>Menu</strong></div>
    <nav class="nav">
      <a href="#" id="navChat" class="active" onclick="showView('chat');return false;"><span class="ico">💬</span>Chat Dashboard</a>
      <a href="#" onclick="currentTab='mine'; loadClients(); showView('chat'); return false;"><span class="ico">👤</span>My Clients</a>
      <a href="#" id="navRem" onclick="showView('reminders'); return false;"><span class="ico">⏰</span>Reminders</a>
      <a href="survey_responses.php"><span class="ico">📝</span>Survey Responses</a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="main">

    <!-- Clients column -->
    <section class="card clients">
      <div class="card-head">
        <div class="search">
          <span>🔎</span>
          <input id="searchBox" placeholder="Search clients...">
        </div>
        <div class="group">
          <button class="btn" onclick="currentTab='all'; loadClients(); document.getElementById('navChat').classList.add('active');">All</button>
          <button class="btn" onclick="currentTab='mine'; loadClients(); document.getElementById('navChat').classList.add('active');">Mine</button>
        </div>
      </div>
      <div class="client-list" id="clientList"></div>
    </section>

    <!-- Chat column -->
    <section class="card chat" id="chatPane">
      <div class="chat-head">
        <div class="who" id="chatTitle">Select a client to view messages</div>
        <div style="opacity:.85">SkyTruFiber</div>
      </div>

      <div class="chat-body" id="messages"></div>

      <div class="typing" id="typingRow">
        <div class="dot"></div><div class="dot"></div><div class="dot"></div>
        <span>Client is typing…</span>
      </div>

      <div class="chat-input" id="inputRow" style="display:none;">
        <input id="msg" placeholder="Type your reply…">
        <button onclick="sendMsg()">Send</button>
      </div>
    </section>

    <!-- Reminders column (replaces chat when selected) -->
    <section class="card" id="remindersPane" style="display:none;">
      <div class="rem-head"><strong>⏰ Reminders</strong></div>
      <div class="rem-body">
        <div id="remListWrap" style="margin-bottom:12px; overflow:auto;">
          <table class="table" id="remTable">
            <thead><tr>
              <th>#</th><th>Client</th><th>CSR</th><th>Type</th><th>Status</th><th>Sent At</th>
            </tr></thead>
            <tbody></tbody>
          </table>
        </div>

        <div class="group">
          <select id="remClient" class="select"></select>
          <select id="remType" class="select">
            <option value="1_WEEK">1 Week Before Due</option>
            <option value="3_DAYS">3 Days Before Due</option>
          </select>
          <button class="btn brand" onclick="createReminder()">Create reminder</button>
        </div>
      </div>
    </section>

  </main>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
/* =============================
   JS STATE
   ============================= */
let currentTab = 'all';
let clientId = 0;
const csrUser = <?= json_encode($csr_user) ?>;
const csrFullname = <?= json_encode($csr_fullname) ?>;

/* =============================
   VIEW CONTROLS
   ============================= */
function showView(which){
  const chatPane = document.getElementById('chatPane');
  const remPane  = document.getElementById('remindersPane');

  if(which==='reminders'){
    document.getElementById('navChat').classList.remove('active');
    document.getElementById('navRem').classList.add('active');
    remPane.style.display='block';
    chatPane.style.display='none';
    loadReminders();
    loadClientDropdown();
  }else{
    document.getElementById('navRem').classList.remove('active');
    document.getElementById('navChat').classList.add('active');
    remPane.style.display='none';
    chatPane.style.display='grid';
  }
}

/* =============================
   THEME
   ============================= */
function toggleTheme(){
  const isDark = document.documentElement.classList.toggle('dark');
  document.getElementById('themeLabel').textContent = isDark ? '🌙 Dark' : '🌞 Light';
  const body = new URLSearchParams();
  body.set('theme', isDark ? 'dark' : 'light');
  fetch('csr_dashboard.php?ajax=theme',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});
}
document.getElementById('themeLabel').textContent =
  document.documentElement.classList.contains('dark') ? '🌙 Dark' : '🌞 Light';

/* =============================
   CLIENTS
   ============================= */
function loadClients(){
  fetch('csr_dashboard.php?ajax=clients&tab='+currentTab)
    .then(r=>r.text())
    .then(html=>{
      const list = document.getElementById('clientList');
      list.innerHTML = html;
      const items = list.querySelectorAll('.client-item');

      // Filter by search
      const q = document.getElementById('searchBox').value?.toLowerCase() || '';
      items.forEach(it=>{
        const nm = it.dataset.name.toLowerCase();
        it.style.display = nm.includes(q) ? '' : 'none';
      });

      items.forEach(it=>{
        it.onclick = ()=>{
          document.querySelectorAll('.client-item').forEach(i=>i.classList.remove('active'));
          it.classList.add('active');
          const name= it.dataset.name;
          const owner= it.dataset.csr;
          clientId = parseInt(it.dataset.id,10);
          document.getElementById('chatTitle').textContent = 'Chat with ' + name;
          loadChat(owner===csrUser, owner);
          showView('chat');
        };
      });
    });
}
document.getElementById('searchBox').addEventListener('input', loadClients);

function assignClient(id){
  const body=new URLSearchParams(); body.set('client_id',id);
  fetch('csr_dashboard.php?ajax=assign',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
  .then(r=>r.text()).then(t=>{
    if(t==='ok') toast('Client assigned to you.');
    if(t==='taken') toast('Client is already assigned.');
    loadClients();
  });
}
function unassignClient(id){
  if(!confirm('Unassign this client?')) return;
  const body=new URLSearchParams(); body.set('client_id',id);
  fetch('csr_dashboard.php?ajax=unassign',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
  .then(r=>r.text()).then(t=>{
    toast(t==='ok'?'Client unassigned.':'Failed.');
    loadClients();
  });
}

/* =============================
   CHAT
   ============================= */
let lastScrollH = 0;

function loadChat(isMine=false, assignedTo=''){
  if(!clientId) return;
  fetch('../SKYTRUFIBER/load_chat.php?client_id='+clientId)
    .then(r=>r.json())
    .then(list=>{
      const box = document.getElementById('messages');
      box.innerHTML='';
      let lastMonth='';

      list.forEach(m=>{
        const d = new Date(m.time);
        const monthName = d.toLocaleString('default',{month:'long'});
        const monthGroup = monthName+' '+d.getFullYear();
        if(monthGroup!==lastMonth){
          const sep = document.createElement('div');
          sep.className='month-label';
          sep.textContent='📅 '+monthGroup;
          box.appendChild(sep);
          lastMonth=monthGroup;
        }

        const msg = document.createElement('div');
        msg.className='msg '+(m.sender_type==='csr'?'csr':'client');

        const who = document.createElement('div');
        who.className='who';
        who.textContent = (m.sender_type==='csr')?(m.csr_fullname||m.assigned_csr||'CSR'):(m.client_name||'Client');
        const txt = document.createElement('div');
        txt.className='text'; txt.textContent=m.message;
        const tm = document.createElement('div');
        tm.className='time';
        tm.textContent = new Date(m.time).toLocaleString();

        msg.appendChild(who); msg.appendChild(txt); msg.appendChild(tm);
        box.appendChild(msg);
      });

      box.scrollTop = box.scrollHeight;
      document.getElementById('inputRow').style.display = isMine ? 'flex' : 'none';
    });
}

function sendMsg(){
  const input = document.getElementById('msg');
  const text = input.value.trim();
  if(!text || !clientId) return;
  const body = new URLSearchParams();
  body.set('sender_type','csr');
  body.set('message',text);
  body.set('csr_user',csrUser);
  body.set('csr_fullname',csrFullname);
  body.set('client_id',String(clientId));
  fetch('../SKYTRUFIBER/save_chat.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
    .then(()=>{ input.value=''; loadChat(true); });
}

/* Live updates (SSE or poll) */
if(!!window.EventSource){
  const sse = new EventSource('../SKYTRUFIBER/realtime_updates.php');
  sse.addEventListener('update', ()=>{
    if(clientId) {
      const before = document.getElementById('messages').scrollHeight;
      loadChat(); loadClients();
      const after = document.getElementById('messages').scrollHeight;
      if(after > before) toast('New message received');
    } else {
      loadClients();
    }
  });
} else {
  setInterval(()=>{ if(clientId) loadChat(); loadClients(); }, 3500);
}

/* =============================
   REMINDERS
   ============================= */
function loadReminders(){
  fetch('csr_dashboard.php?ajax=load_reminders')
  .then(r=>r.json())
  .then(rows=>{
    const tbody = document.querySelector('#remTable tbody');
    tbody.innerHTML='';
    rows.forEach(r=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.id}</td>
        <td>${r.client_name||''}</td>
        <td>${r.csr_username||''}</td>
        <td>${r.reminder_type||''}</td>
        <td>${r.status||''}</td>
        <td>${r.sent_at||''}</td>
      `;
      tbody.appendChild(tr);
    });
  });
}

function loadClientDropdown(){
  fetch('csr_dashboard.php?ajax=clients&tab=all')
    .then(r=>r.text())
    .then(html=>{
      const temp = document.createElement('div'); temp.innerHTML = html;
      const sel = document.getElementById('remClient');
      sel.innerHTML = '';
      temp.querySelectorAll('.client-item').forEach(el=>{
        const opt = document.createElement('option');
        opt.value = el.dataset.id;
        opt.textContent = el.dataset.name;
        sel.appendChild(opt);
      });
    });
}

function createReminder(){
  const cid = document.getElementById('remClient').value;
  const type = document.getElementById('remType').value;
  const body = new URLSearchParams(); body.set('client_id',cid); body.set('type',type);
  fetch('csr_dashboard.php?ajax=create_reminder',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
  .then(r=>r.text()).then(t=>{
    toast('Reminder created.'); loadReminders();
  });
}

/* =============================
   TOAST
   ============================= */
function toast(text){
  const el = document.getElementById('toast');
  el.textContent = text;
  el.style.display='block';
  setTimeout(()=>el.style.display='none', 2500);
}

/* =============================
   INIT
   ============================= */
window.addEventListener('load', ()=>{
  loadClients();
});
</script>
</body>
</html>
