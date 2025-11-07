<?php
session_start();
include '../db_connect.php';

// Ensure CSR is logged in
if (!isset($_SESSION['csr_user'])) {
  header("Location: csr_login.php");
  exit;
}

$csr_user = $_SESSION['csr_user'];

// Fetch CSR full name
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $csr_user);
$stmt->execute();
$res = $stmt->get_result();
$csr_fullname = ($res && $res->num_rows > 0) ? $res->fetch_assoc()['full_name'] : $csr_user;

// ✅ Set logo path (adjust as needed)
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ===== AJAX HANDLERS ===== */
if (isset($_GET['ajax'])) {

  // Load Clients
  if ($_GET['ajax'] === 'clients') {
    $tab = $_GET['tab'] ?? 'all';
    if ($tab === 'mine') {
      $stmt = $conn->prepare("
        SELECT c.id, c.name, c.assigned_csr, MAX(ch.created_at) AS last_chat
        FROM clients c
        LEFT JOIN chat ch ON ch.client_id = c.id
        WHERE c.assigned_csr = ?
        GROUP BY c.id, c.name, c.assigned_csr
        ORDER BY last_chat DESC
      ");
      $stmt->bind_param("s", $csr_user);
      $stmt->execute();
      $clients = $stmt->get_result();
    } else {
      $clients = $conn->query("
        SELECT c.id, c.name, c.assigned_csr, MAX(ch.created_at) AS last_chat
        FROM clients c
        LEFT JOIN chat ch ON ch.client_id = c.id
        GROUP BY c.id, c.name, c.assigned_csr
        ORDER BY last_chat DESC
      ");
    }

    while ($row = $clients->fetch_assoc()) {
      $assigned = $row['assigned_csr'] ?: 'Unassigned';
      $owned = ($assigned === $csr_user);
      $canAssign = ($assigned === 'Unassigned');
      $btn = '';
      if ($canAssign) {
        $btn = "<button class='assign-btn' title='Assign to me' onclick='assignClient({$row['id']})'>＋</button>";
      } elseif ($owned) {
        $btn = "<button class='unassign-btn' title='Unassign client' onclick='unassignClient({$row['id']})'>−</button>";
      } else {
        $btn = "<button class='locked-btn' disabled title='Already assigned'>🔒</button>";
      }

      echo "
        <div class='client-item' data-id='{$row['id']}' data-csr='".htmlspecialchars($assigned, ENT_QUOTES)."'>
          <div class='client-info'>
            <strong>".htmlspecialchars($row['name'])."</strong><br>
            <small>Assigned: ".htmlspecialchars($assigned)."</small>
          </div>
          $btn
        </div>
      ";
    }
    exit;
  }

  // Assign client
  if ($_GET['ajax'] === 'assign' && isset($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];
    $chk = $conn->query("SELECT assigned_csr FROM clients WHERE id=$id");
    $r = $chk->fetch_assoc();
    if ($r['assigned_csr'] && $r['assigned_csr'] !== 'Unassigned' && $r['assigned_csr'] !== '') {
      echo 'taken';
      exit;
    }
    $stmt = $conn->prepare("UPDATE clients SET assigned_csr = ? WHERE id = ?");
    $stmt->bind_param("si", $csr_user, $id);
    echo $stmt->execute() ? 'ok' : 'fail';
    exit;
  }

  // Unassign client
  if ($_GET['ajax'] === 'unassign' && isset($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];
    $stmt = $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned' WHERE id = ? AND assigned_csr = ?");
    $stmt->bind_param("is", $id, $csr_user);
    echo $stmt->execute() ? 'ok' : 'fail';
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — SkyTruFiber</title>
<style>
body {
  font-family:"Segoe UI",Arial,sans-serif;
  margin:0; height:100vh; display:flex;
  background:#f2fff2; overflow:hidden; position:relative;
}
body::before {
  content:""; position:absolute; inset:0;
  background:url('<?= $logoPath ?>') no-repeat center center;
  background-size:700px auto; opacity:0.05; z-index:0;
}

/* Sidebar */
#sidebar {
  width:240px; background:#009900; color:#fff;
  position:fixed; left:0; top:0; bottom:0;
  transform:translateX(-100%); transition:.3s;
  display:flex; flex-direction:column; z-index:2;
}
#sidebar.active { transform:translateX(0); }
#sidebar h2 { margin:0; padding:15px; background:#007a00; text-align:center;
  display:flex; align-items:center; justify-content:center; gap:10px; }
#sidebar h2 img { height:28px; }
#sidebar a { color:#fff; text-decoration:none; padding:15px 20px; display:block; font-weight:600; }
#sidebar a:hover { background:#00b300; }

/* Header */
header {
  background:#00aa00; color:#fff; display:flex;
  justify-content:space-between; align-items:center;
  padding:10px 20px; font-weight:700; z-index:1;
}
header .title { display:flex; align-items:center; gap:15px; font-size:18px; }
header img { height:45px; }

/* Tabs */
#tabs { display:flex; gap:8px; padding:8px 20px; background:rgba(230,255,230,0.95); border-bottom:1px solid #ccc; }
.tab { padding:8px 14px; border-radius:8px; cursor:pointer; color:#007a00; font-weight:700; }
.tab.active { background:#009900; color:#fff; }

#main-content { flex:1; margin-left:0; transition:.3s; display:flex; flex-direction:column; z-index:1; }
#main-content.shifted { margin-left:240px; }

#container { flex:1; display:flex; overflow:hidden; }
#client-list { width:300px; background:#fff; border-right:1px solid #ccc; overflow-y:auto; padding:10px; }

.client-item {
  display:flex; justify-content:space-between; align-items:center;
  background:#fff; margin:6px 0; padding:8px; border-radius:10px;
  box-shadow:0 1px 4px rgba(0,0,0,.1); cursor:pointer;
}
.client-item:hover { background:#e6ffe6; }
.client-item.active { background:#c8f8c8; font-weight:700; }

.assign-btn, .unassign-btn, .locked-btn {
  border:none; color:#fff; border-radius:50%; font-size:18px;
  width:30px; height:30px; cursor:pointer;
}
.assign-btn { background:#00aa00; }
.unassign-btn { background:#cc0000; }
.locked-btn { background:#777; cursor:not-allowed; }

#chat-area { flex:1; display:flex; flex-direction:column; background:#fff; position:relative; }
#messages::before {
  content:""; position:absolute; top:50%; left:50%;
  width:400px; height:400px;
  background:url('<?= $logoPath ?>') no-repeat center center;
  background-size:contain; opacity:0.05;
  transform:translate(-50%,-50%); pointer-events:none;
}
#chat-header { background:#009900; color:#fff; padding:10px; font-weight:800; }
.bubble { max-width:70%; padding:10px 12px; border-radius:12px; margin:6px 0; clear:both; font-size:14px; }
.client { background:#e9ffe9; float:left; }
.csr { background:#ccf0ff; float:right; }
.timestamp { display:block; font-size:11px; color:#777; margin-top:4px; text-align:right; }

.input { display:flex; gap:8px; border-top:1px solid #ddd; padding:10px; background:#fff; }
.input input { flex:1; border:1px solid #ccc; padding:10px; border-radius:8px; }
.input button { background:#00aa00; border:none; color:#fff; padding:10px 16px; border-radius:8px; cursor:pointer; font-weight:700; }
.input button:hover { background:#007a00; }

.month-label { font-size:13px; color:#007700; text-align:center; margin:8px 0; background:#eaffea; border-radius:8px; padding:4px; }
</style>
</head>
<body>
<div id="sidebar">
  <h2><img src="<?= $logoPath ?>" alt="Logo"> Menu</h2>
  <a href="csr_dashboard.php?tab=all">💬 Chat Dashboard</a>
  <a href="csr_dashboard.php?tab=mine">👥 My Clients</a>
  <a href="survey_responses.php">📝 Survey Responses</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<div id="main-content">
  <header>
    <button id="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="title">
      <img src="<?= $logoPath ?>" alt="Logo">
      <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
    <a href="csr_logout.php">Logout</a>
  </header>

  <div id="tabs">
    <div id="tab-all" class="tab" onclick="goTo('csr_dashboard.php?tab=all')">💬 All Clients</div>
    <div id="tab-mine" class="tab" onclick="goTo('csr_dashboard.php?tab=mine')">🧑‍💼 My Clients</div>
    <div id="tab-survey" class="tab" onclick="goTo('survey_responses.php')">📝 Survey Responses</div>
  </div>

  <div id="container">
    <div id="client-list"></div>
    <div id="chat-area">
      <div id="chat-header">
        <span id="chat-title">Select a client to view messages</span>
      </div>
      <div id="messages"></div>
      <div class="input" id="inputRow" style="display:none;">
        <input id="msg" placeholder="Type a reply…">
        <button onclick="sendMsg()">Send</button>
      </div>
    </div>
  </div>
</div>

<script>
let currentTab='all', clientId=0, lastMsgCount=0;
const csrUser="<?= htmlspecialchars($csr_user) ?>";
const csrFullname="<?= htmlspecialchars($csr_fullname) ?>";

function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('active');
  document.getElementById('main-content').classList.toggle('shifted');
}
function goTo(url){window.location.href=url;}

function switchTab(tab){
  currentTab=tab;
  document.getElementById('tab-all').classList.toggle('active',tab==='all');
  document.getElementById('tab-mine').classList.toggle('active',tab==='mine');
  loadClients();
}

function loadClients(){
  fetch('csr_dashboard.php?ajax=clients&tab='+currentTab)
    .then(r=>r.text()).then(html=>{
      document.getElementById('client-list').innerHTML=html;
      document.querySelectorAll('.client-item').forEach(el=>{
        el.addEventListener('click',()=>{
          const clientName=el.querySelector('strong').textContent;
          const assignedTo=el.getAttribute('data-csr');
          const isMine=(assignedTo===csrUser);
          document.querySelectorAll('.client-item').forEach(i=>i.classList.remove('active'));
          el.classList.add('active');
          clientId=parseInt(el.getAttribute('data-id'),10);
          document.getElementById('chat-title').textContent='Chat with '+clientName;
          loadChat(isMine,assignedTo);
        });
      });
    });
}

function loadChat(isMine=false,assignedTo=''){
  if(!clientId)return;
  fetch('../SKYTRUFIBER/load_chat.php?client_id='+clientId)
    .then(r=>r.json()).then(list=>{
      const box=document.getElementById('messages');
      box.innerHTML='';
      let lastMonth='';
      list.forEach(m=>{
        const d=new Date(m.time);
        const monthName=d.toLocaleString('default',{month:'long'});
        const year=d.getFullYear();
        const monthGroup=monthName+' '+year;
        if(monthGroup!==lastMonth){
          const monthLabel=document.createElement('div');
          monthLabel.className='month-label';
          monthLabel.textContent='📅 '+monthGroup;
          box.appendChild(monthLabel);
          lastMonth=monthGroup;
        }
        const b=document.createElement('div');
        b.className='bubble '+(m.sender_type==='csr'?'csr':'client');
        const who=(m.sender_type==='csr')?(m.csr_fullname||m.assigned_csr||'CSR'):(m.client_name||'Client');
        const t=document.createElement('span');t.className='timestamp';t.textContent=new Date(m.time).toLocaleString();
        b.textContent=who+': '+m.message;b.appendChild(t);box.appendChild(b);
      });
      box.scrollTop=box.scrollHeight;
      document.getElementById('inputRow').style.display=isMine?'flex':'none';
    });
}

function assignClient(id){
  fetch('csr_dashboard.php?ajax=assign',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'client_id='+encodeURIComponent(id)
  }).then(r=>r.text()).then(resp=>{
    if(resp==='ok'){alert('Client assigned to you.');loadClients();}
    else if(resp==='taken'){alert('Already assigned.');loadClients();}
  });
}

function unassignClient(id){
  if(!confirm('Unassign this client?'))return;
  fetch('csr_dashboard.php?ajax=unassign',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'client_id='+encodeURIComponent(id)
  }).then(()=>loadClients());
}

function sendMsg(){
  const input=document.getElementById('msg');
  const text=input.value.trim();
  if(!text||!clientId)return;
  const body=new URLSearchParams();
  body.set('sender_type','csr');
  body.set('message',text);
  body.set('csr_user',csrUser);
  body.set('csr_fullname',csrFullname);
  body.set('client_id',String(clientId));
  fetch('../SKYTRUFIBER/save_chat.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
    .then(()=>{input.value='';loadChat(true);});
}

// Load correct tab on start
window.onload=()=>{
  const params=new URLSearchParams(window.location.search);
  const tab=params.get('tab');
  if(tab==='mine'){switchTab('mine');}
  else{switchTab('all');}

  // Real-time updates (SSE)
  if(!!window.EventSource){
    const evtSource=new EventSource('../SKYTRUFIBER/realtime_updates.php');
    evtSource.addEventListener('update',()=>{if(clientId)loadChat();loadClients();});
  }else{
    setInterval(()=>{if(clientId)loadChat();loadClients();},3000);
  }
};
</script>
</body>
</html>
