<?php
/**
 * CSR Dashboard — FINAL VERSION with collapsible right column
 */

session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

$st = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username = :u");
$st->execute([':u' => $csr_user]);
$row = $st->fetch(PDO::FETCH_ASSOC);

$csr_fullname = $row['full_name'] ?? $csr_user;

// Logo detection
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/*************************************
 * AJAX ENDPOINTS
 *************************************/
if (isset($_GET['ajax'])) {

    // Load client list
    if ($_GET['ajax'] === 'clients') {
        $tab = $_GET['tab'] ?? 'all';
        $sql = "SELECT c.id, c.name, c.assigned_csr,
                       (SELECT email FROM users u WHERE u.full_name = c.name LIMIT 1) AS email,
                       MAX(ch.created_at) AS last_chat
                FROM clients c
                LEFT JOIN chat ch ON ch.client_id = c.id";
        if ($tab === 'mine') {
            $sql .= " WHERE c.assigned_csr = :csr";
        }
        $sql .= " GROUP BY c.id, c.name, c.assigned_csr, email ORDER BY last_chat DESC NULLS LAST";

        $st2 = $conn->prepare($sql);
        if ($tab === 'mine') {
            $st2->execute([':csr' => $csr_user]);
        } else {
            $st2->execute();
        }

        while ($c = $st2->fetch(PDO::FETCH_ASSOC)) {

            $assigned = $c['assigned_csr'] ?: 'Unassigned';
            $owned    = ($assigned === $csr_user);

            if ($assigned === 'Unassigned') {
                $btn = "<button class='pill green' onclick='assignClient({$c['id']})'>＋</button>";
            } elseif ($owned) {
                $btn = "<button class='pill red' onclick='unassignClient({$c['id']})'>−</button>";
            } else {
                $btn = "<button class='pill gray' disabled>🔒</button>";
            }

            echo "
            <div class='client-item' 
                data-id='{$c['id']}' 
                data-name='".htmlspecialchars($c['name'], ENT_QUOTES)."' 
                data-csr='".htmlspecialchars($assigned)."'>
                
                <div>
                    <div class='client-name'>".htmlspecialchars($c['name'])."</div>
                    <div class='client-email'>".htmlspecialchars($c['email'])."</div>
                    <div class='client-assign'>Assigned: {$assigned}</div>
                </div>

                <div>{$btn}</div>
            </div>";
        }
        exit;
    }

    // Load chat messages
    if ($_GET['ajax'] === 'load_chat' && isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];

        $q = $conn->prepare("SELECT ch.sender_type, ch.message, ch.created_at,
                                    c.name AS client_name, ch.csr_fullname, ch.assigned_csr
                             FROM chat ch
                             JOIN clients c ON c.id = ch.client_id
                             WHERE ch.client_id = :cid
                             ORDER BY ch.created_at ASC");
        $q->execute([':cid' => $cid]);
        echo json_encode($q->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Client profile for avatar
    if ($_GET['ajax'] === 'client_profile' && isset($_GET['name'])) {
        $name = trim($_GET['name']);
        $p = $conn->prepare("SELECT email, gender FROM users WHERE full_name = :n LIMIT 1");
        $p->execute([':n' => $name]);
        echo json_encode($p->fetch(PDO::FETCH_ASSOC));
        exit;
    }

    // Assign client
    if ($_GET['ajax'] === 'assign') {
        $id = (int)$_POST['client_id'];
        $check = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
        $check->execute([':id' => $id]);
        $cur = $check->fetch(PDO::FETCH_ASSOC);
        if ($cur && $cur['assigned_csr'] !== 'Unassigned' && $cur['assigned_csr'] !== null) {
            echo 'taken'; exit;
        }
        $ok = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id")
                  ->execute([':csr' => $csr_user, ':id' => $id]);
        echo $ok ? 'ok' : 'fail';
        exit;
    }

    // Unassign
    if ($_GET['ajax'] === 'unassign') {
        $id = (int)$_POST['client_id'];
        $ok = $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned' WHERE id = :id AND assigned_csr = :csr")
                  ->execute([':id' => $id, ':csr' => $csr_user]);
        echo $ok ? 'ok' : 'fail';
        exit;
    }
}

/*************************************
 * END AJAX
 *************************************/
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?=htmlspecialchars($csr_fullname)?></title>
<style>
:root {
  --green:#0aa05b;
  --line:#dfe6e2;
  --soft:#f8fff8;
  --bg:#fff;
}

/* General */
body { margin:0; font-family:Segoe UI; background:var(--soft); overflow:hidden; }

/* Sidebar */
#sidebar {
  position:fixed; top:0; left:0; width:260px; height:100vh;
  background:#07804a; color:#fff; transform:translateX(-100%);
  transition:.25s; z-index:100;
}
#sidebar.active { transform:translateX(0); }
#sidebar h2 { padding:20px; margin:0; background:#056b3d; }
#sidebar a { display:block; padding:14px 18px; color:#fff; text-decoration:none; }
#overlay { position:fixed; inset:0; display:none; background:rgba(0,0,0,0.3); z-index:99; }

/* Header */
header {
  height:64px; background:linear-gradient(135deg,#0fb572,#0aa05b);
  display:flex; align-items:center; justify-content:space-between;
  padding:0 18px; color:#fff;
}
#hamb { font-size:26px; background:none; border:none; color:#fff; cursor:pointer; }

/* Tabs */
.tabs {
  display:flex; gap:10px; padding:12px 18px;
  background:var(--soft); border-bottom:1px solid var(--line);
}
.tab { padding:8px 16px; border-radius:20px; background:#fff;
       border:1px solid var(--line); cursor:pointer; }
.tab.active { background:#07804a; color:#fff; border-color:#07804a; }

/* Layout */
#main { display:grid; grid-template-columns:340px 1fr;
        height:calc(100vh - 112px); }

/* Clients Left */
#client-col { border-right:1px solid var(--line); overflow:auto; background:#fff; }
.client-item {
  margin:10px; padding:12px; border-radius:12px; background:#fff;
  border:1px solid var(--line); display:flex; justify-content:space-between;
}
.client-name { font-weight:bold; }
.client-email { font-size:12px; color:#06723e; }

/* Pill buttons */
.pill { border:none; border-radius:999px; padding:7px 10px; color:#fff; cursor:pointer; font-weight:bold; }
.pill.green { background:#10c076; }
.pill.red { background:#e16161; }
.pill.gray { background:#777; }

/* Chat Right */
#chat-col { position:relative; display:flex; flex-direction:column; background:#fff; }

/* COLLAPSED BEHAVIOR */
#chat-col.collapsed {
  width:80px !important;
  min-width:80px !important;
  max-width:80px !important;
  overflow:hidden;
  transition:0.3s;
}

#chat-col.collapsed #messages,
#chat-col.collapsed #input {
  display:none !important;
}

#chat-col.collapsed #chat-head .chat-title span {
  display:none;
}

/* Collapse button */
#collapseBtn {
  position:absolute; top:14px; right:14px;
  width:36px; height:36px; border-radius:50%;
  background:#fff; border:1px solid var(--line);
  display:flex; align-items:center; justify-content:center;
  font-size:20px; font-weight:bold; cursor:pointer;
}

/* Chat header */
#chat-head {
  background:linear-gradient(135deg,#0aa05b,#07804a);
  color:#fff; padding:12px 16px; display:flex; justify-content:space-between; align-items:center;
}

.chat-title { display:flex; align-items:center; gap:10px; }
.avatar { width:36px; height:36px; border-radius:50%; background:#cfeee0;
          display:flex; align-items:center; justify-content:center;
          font-weight:bold; }

/* Messages */
#messages { flex:1; overflow:auto; padding:14px; }
.msg { margin:10px 0; max-width:70%; }
.msg .bubble { padding:10px 14px; border-radius:14px; }
.msg.client { float:left; }
.msg.client .bubble { background:#e8ffee; }
.msg.csr { float:right; }
.msg.csr .bubble { background:#e6f2ff; }

/* Input row */
#input { display:none; padding:10px; border-top:1px solid var(--line); }
#input input { flex:1; padding:10px; border:1px solid var(--line); border-radius:8px; }
#input button { padding:10px 14px; background:#07804a; border:none; color:#fff; border-radius:8px; }
</style>
</head>

<body>

<div id="overlay" onclick="toggleSidebar(false)"></div>

<div id="sidebar">
  <h2>Menu</h2>
  <a onclick="switchTab('all')">💬 All Clients</a>
  <a onclick="switchTab('mine')">👤 My Clients</a>
  <a onclick="switchTab('rem')">⏰ Reminders</a>
  <a href="survey_responses.php">📝 Surveys</a>
  <a href="update_profile.php">👤 Edit Profile</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<header>
  <button id="hamb" onclick="toggleSidebar()">☰</button>
  <div>CSR Dashboard — <?=htmlspecialchars($csr_fullname)?></div>
</header>

<div class="tabs">
  <div id="tab-all" class="tab active" onclick="switchTab('all')">💬 All Clients</div>
  <div id="tab-mine" class="tab" onclick="switchTab('mine')">👤 My Clients</div>
  <div id="tab-rem" class="tab" onclick="switchTab('rem')">⏰ Reminders</div>
  <div class="tab" onclick="location.href='survey_responses.php'">📝 Surveys</div>
  <div class="tab" onclick="location.href='update_profile.php'">👤 Edit Profile</div>
</div>

<div id="main">

<!-- Clients list -->
<div id="client-col"></div>

<!-- Chat panel -->
<div id="chat-col">

  <button id="collapseBtn" onclick="toggleRight()">…</button>

  <div id="chat-head">
    <div class="chat-title">
      <div class="avatar" id="chatAvatar">?</div>
      <span id="chat-title">Select a client</span>
    </div>
    <div>i</div>
  </div>

  <div id="messages"></div>

  <div id="input">
    <input id="msg" placeholder="Type a reply…">
    <button onclick="sendMsg()">Send</button>
  </div>

</div>

</div>

<script>
// Sidebar
function toggleSidebar(force){
  const sb = document.getElementById('sidebar');
  const ol = document.getElementById('overlay');
  const active = sb.classList.contains('active');
  if ((force===true)||(!active && force!==false)) { sb.classList.add('active'); ol.style.display='block'; }
  else { sb.classList.remove('active'); ol.style.display='none'; }
}

// Collapse Right Chat column
function toggleRight(){
  const col = document.getElementById('chat-col');
  const btn = document.getElementById('collapseBtn');
  if (!col.classList.contains('collapsed')) {
    col.classList.add('collapsed');
    btn.textContent = 'i';
  } else {
    col.classList.remove('collapsed');
    btn.textContent = '…';
  }
}

// Tabs
let currentTab='all';
function switchTab(t){
  currentTab = t;
  ['all','mine','rem'].forEach(x => document.getElementById('tab-'+x)?.classList.remove('active'));
  document.getElementById('tab-'+t).classList.add('active');

  if (t === 'rem'){
    // TODO: reminders
  } else {
    loadClients();
  }
}

// Load clients
function loadClients(){
  fetch('csr_dashboard.php?ajax=clients&tab='+currentTab)
    .then(r=>r.text())
    .then(html=>{
      document.getElementById('client-col').innerHTML = html;
      document.querySelectorAll('.client-item').forEach(el=>{
        el.onclick = ()=>selectClient(el);
      });
    });
}

// Select client
let currentClient=0;
let currentClientAssignee='';
function selectClient(el){
  currentClient = el.dataset.id;
  currentClientAssignee = el.dataset.csr;
  document.getElementById('chat-title').textContent = el.dataset.name;

  document.getElementById('input').style.display = 
    (currentClientAssignee==='Unassigned' || currentClientAssignee===<?=$csr_user?>) ? 'flex' : 'none';

  loadChat();
  loadAvatar(el.dataset.name);
}

// Avatar
function loadAvatar(name){
  fetch('csr_dashboard.php?ajax=client_profile&name='+encodeURIComponent(name))
    .then(r=>r.json())
    .then(p=>{
      const slot = document.getElementById('chatAvatar');
      slot.innerHTML='';
      if (p && p.gender){
        if (p.gender.toLowerCase()==='female'){
          slot.textContent='🐧';
        } else {
          slot.textContent='🦁';
        }
      } else {
        slot.textContent = name.split(" ").map(s=>s[0]).join("");
      }
    });
}

// Chat
function loadChat(){
  if(!currentClient) return;
  fetch('csr_dashboard.php?ajax=load_chat&client_id='+currentClient)
    .then(r=>r.json())
    .then(rows=>{
      const msgBox = document.getElementById('messages');
      msgBox.innerHTML='';
      rows.forEach(m=>{
        const div = document.createElement('div');
        div.className='msg '+(m.sender_type==='csr'?'csr':'client');
        div.innerHTML = `<div class="bubble"><strong>${m.sender_type==='csr'?(m.csr_fullname||'CSR'):m.client_name}:</strong> ${m.message}</div>`;
        msgBox.appendChild(div);
      });
      msgBox.scrollTop = msgBox.scrollHeight;
    });
}

function sendMsg(){
  if(!currentClient) return;
  const input = document.getElementById('msg');
  const text = input.value.trim();
  if(!text) return;
  const body = new URLSearchParams({
    sender_type:'csr',
    client_id:currentClient,
    message:text
  });
  fetch('../SKYTRUFIBER/save_chat.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
  .then(()=>{input.value='';loadChat();});
}
loadClients();
</script>

</body>
</html>
