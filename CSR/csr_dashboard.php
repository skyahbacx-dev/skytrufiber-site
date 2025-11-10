<?php
/**
 * csr_dashboard.php  — SkyTruFiber
 * ----------------------------------------------------------
 * - Collapsible sidebar (stays closed unless toggled)
 * - Tabs: All Clients | My Clients | Reminders | Survey | Edit Profile
 * - Client list shows email (from users table by full_name)
 * - Aesthetic chat bubbles, watermark, timestamps
 * - Reply LOCK if conversation assigned to another CSR
 * - Floating circular ⓘ button (no background) opens a right drawer
 *   with client details (email, date installed, assignment, quick notes)
 * - Reminders panel shows upcoming / due / sent banners (preview only)
 */

session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) { header("Location: csr_login.php"); exit; }
$csr_user = $_SESSION['csr_user'];

$st = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username=:u LIMIT 1");
$st->execute([':u'=>$csr_user]);
$csr = $st->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $csr['full_name'] ?? $csr_user;

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ──────────────────────────────────────────────────────────────
| AJAX ENDPOINTS
|────────────────────────────────────────────────────────────── */
if (isset($_GET['ajax'])) {

  // CLIENT LIST (all / mine)
  if ($_GET['ajax'] === 'clients') {
    $tab = $_GET['tab'] ?? 'all';

    $sql = "
      SELECT c.id, c.name, c.assigned_csr,
             (SELECT email FROM users u WHERE u.full_name = c.name LIMIT 1) AS email,
             MAX(ch.created_at) AS last_chat
      FROM clients c
      LEFT JOIN chat ch ON ch.client_id = c.id
    ";
    $where = ($tab === 'mine') ? " WHERE c.assigned_csr = :csr " : "";
    $sql .= $where . " GROUP BY c.id, c.name, c.assigned_csr ORDER BY last_chat DESC NULLS LAST";

    $q = $conn->prepare($sql);
    if ($tab === 'mine') $q->execute([':csr'=>$csr_user]); else $q->execute();

    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
      $assigned = $row['assigned_csr'] ?: 'Unassigned';
      $owned = ($assigned === $csr_user);

      if ($assigned === 'Unassigned') {
        $btn = "<button class='pill green' onclick='assignClient({$row['id']})' title='Assign to me'>＋</button>";
      } elseif ($owned) {
        $btn = "<button class='pill red' onclick='unassignClient({$row['id']})' title='Unassign'>−</button>";
      } else {
        $btn = "<button class='pill gray' disabled title='Assigned to another CSR'>🔒</button>";
      }

      $name = htmlspecialchars($row['name']);
      $email = htmlspecialchars($row['email'] ?? '');
      $assignedH = htmlspecialchars($assigned);

      echo "
        <div class='client-item'
             data-id='{$row['id']}'
             data-name='".htmlspecialchars($row['name'],ENT_QUOTES)."'
             data-csr='".htmlspecialchars($assigned,ENT_QUOTES)."'>
          <div class='client-meta'>
            <div class='client-name'>{$name}</div>
            ".($email ? "<div class='client-email'>{$email}</div>" : "")."
            <div class='client-assign'>Assigned: {$assignedH}</div>
          </div>
          <div class='client-actions'>{$btn}</div>
        </div>
      ";
    }
    exit;
  }

  // LOAD CHAT
  if ($_GET['ajax'] === 'load_chat' && isset($_GET['client_id'])) {
    $cid = (int)$_GET['client_id'];
    $q = $conn->prepare("
      SELECT ch.message, ch.sender_type, ch.created_at, ch.assigned_csr, ch.csr_fullname, c.name AS client_name
      FROM chat ch
      JOIN clients c ON c.id = ch.client_id
      WHERE ch.client_id = :cid
      ORDER BY ch.created_at ASC
    ");
    $q->execute([':cid'=>$cid]);

    $rows = [];
    while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
      $rows[] = [
        'message'      => $r['message'],
        'sender_type'  => $r['sender_type'],
        'time'         => date('Y-m-d H:i:s', strtotime($r['created_at'])),
        'client_name'  => $r['client_name'],
        'assigned_csr' => $r['assigned_csr'],
        'csr_fullname' => $r['csr_fullname']
      ];
    }
    echo json_encode($rows); exit;
  }

  // CLIENT DETAILS for right drawer
  if ($_GET['ajax'] === 'client_details' && isset($_GET['client_id'])) {
    $cid = (int)$_GET['client_id'];
    // Join with users to get email/date_installed by matching full_name to client name
    $q = $conn->prepare("
      SELECT c.id, c.name, c.assigned_csr,
             u.email, u.date_installed
      FROM clients c
      LEFT JOIN users u ON u.full_name = c.name
      WHERE c.id = :cid
      LIMIT 1
    ");
    $q->execute([':cid'=>$cid]);
    $row = $q->fetch(PDO::FETCH_ASSOC) ?: [];
    echo json_encode($row); exit;
  }

  // ASSIGN
  if ($_GET['ajax'] === 'assign' && isset($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];
    $c = $conn->prepare("SELECT assigned_csr FROM clients WHERE id=:id");
    $c->execute([':id'=>$id]);
    $cur = $c->fetch(PDO::FETCH_ASSOC);

    if ($cur && $cur['assigned_csr'] && $cur['assigned_csr'] !== 'Unassigned') {
      echo 'taken'; exit;
    }
    $ok = $conn->prepare("UPDATE clients SET assigned_csr=:c WHERE id=:id")
               ->execute([':c'=>$csr_user, ':id'=>$id]);
    echo $ok ? 'ok' : 'fail'; exit;
  }

  // UNASSIGN
  if ($_GET['ajax'] === 'unassign' && isset($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];
    $ok = $conn->prepare("UPDATE clients SET assigned_csr='Unassigned' WHERE id=:id AND assigned_csr=:c")
               ->execute([':id'=>$id, ':c'=>$csr_user]);
    echo $ok ? 'ok' : 'fail'; exit;
  }

  // REMINDERS preview (based on date_installed same-day cycle)
  if ($_GET['ajax'] === 'reminders') {
    $search = trim($_GET['q'] ?? '');
    $rows = [];

    $u = $conn->query("
      SELECT id, full_name, email, date_installed
      FROM users
      WHERE email IS NOT NULL
      ORDER BY full_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $today = new DateTime('today');
    foreach ($u as $usr) {
      if (!$usr['date_installed']) continue;

      $di = new DateTime($usr['date_installed']);
      $dueDay = (int)$di->format('d');

      $base = new DateTime('first day of this month');
      $due  = (clone $base)->setDate((int)$base->format('Y'), (int)$base->format('m'), min($dueDay, 28));
      if ((int)$today->format('d') > (int)$due->format('d')) {
        $next = new DateTime('first day of next month');
        $due  = (clone $next)->setDate((int)$next->format('Y'), (int)$next->format('m'), min($dueDay, 28));
      }

      $oneWeek  = (clone $due)->modify('-7 days');
      $threeDay = (clone $due)->modify('-3 days');

      $cycle = $due->format('Y-m-d');
      $st = $conn->prepare("
        SELECT reminder_type, status
        FROM reminders
        WHERE client_id = :cid AND cycle_date = :cycle
      ");
      $st->execute([':cid'=>$usr['id'], ':cycle'=>$cycle]);
      $sent = $st->fetchAll(PDO::FETCH_ASSOC);

      $sentMap = [];
      foreach ($sent as $s) $sentMap[$s['reminder_type']] = $s['status'];

      $badges = [];
      if ($today <= $oneWeek && $today->diff($oneWeek)->days <= 7) {
        $badges[] = ['type'=>'1_WEEK', 'status'=>($sentMap['1_WEEK']??'')==='sent'?'sent':'upcoming', 'date'=>$oneWeek->format('Y-m-d')];
      } elseif ($today == $oneWeek) {
        $badges[] = ['type'=>'1_WEEK', 'status'=>($sentMap['1_WEEK']??'')==='sent'?'sent':'due', 'date'=>$oneWeek->format('Y-m-d')];
      }
      if ($today <= $threeDay && $today->diff($threeDay)->days <= 7) {
        $badges[] = ['type'=>'3_DAYS', 'status'=>($sentMap['3_DAYS']??'')==='sent'?'sent':'upcoming', 'date'=>$threeDay->format('Y-m-d')];
      } elseif ($today == $threeDay) {
        $badges[] = ['type'=>'3_DAYS', 'status'=>($sentMap['3_DAYS']??'')==='sent'?'sent':'due', 'date'=>$threeDay->format('Y-m-d')];
      }

      if (!$badges) continue;

      if ($search) {
        $hay = strtolower(($usr['full_name']??'').' '.($usr['email']??''));
        if (strpos($hay, strtolower($search)) === false) continue;
      }

      $rows[] = [
        'user_id'=>$usr['id'],
        'name'=>$usr['full_name'],
        'email'=>$usr['email'],
        'due'=>$due->format('Y-m-d'),
        'banners'=>$badges
      ];
    }

    echo json_encode($rows); exit;
  }

  http_response_code(400);
  echo 'bad';
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
:root{
  --green:#0aa835; --green-600:#087d29; --green-700:#066321;
  --light:#f6fff6; --soft:#eaffea; --bg:#ffffff; --line:#e8e8e8;
  --csr:#e2f2ff; --client:#eeffef; --text:#1a1a1a;
}
*{box-sizing:border-box}
body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:var(--light);color:var(--text);overflow:hidden}

/* Header */
header{height:62px;background:linear-gradient(90deg,#0aa835,#0a9a59);color:#fff;
  display:flex;align-items:center;justify-content:space-between;padding:0 16px;font-weight:800}
.brand{display:flex;align-items:center;gap:10px}
.brand img{height:38px;border-radius:8px;filter:drop-shadow(0 2px 8px rgba(0,0,0,.15))}
#hamb{cursor:pointer;font-size:26px;background:none;border:none;color:#fff}

/* Sidebar */
#overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;z-index:8}
#sidebar{position:fixed;top:0;left:0;width:260px;height:100vh;background:#095f22;
  color:#fff;transform:translateX(-100%);transition:.25s;z-index:9;box-shadow:5px 0 18px rgba(0,0,0,.25)}
#sidebar.active{transform:translateX(0)}
#sidebar h2{margin:0;padding:18px;background:#0b4d1c;text-align:center;font-size:18px}
#sidebar a{display:block;padding:14px 18px;text-decoration:none;color:#fff;font-weight:600;border-bottom:1px solid rgba(255,255,255,.07)}
#sidebar a:hover{background:#0ea642}

/* Tabs */
.tabs{display:flex;gap:10px;background:var(--soft);padding:10px 14px;border-bottom:1px solid #cfe8cf}
.tab{padding:9px 14px;border-radius:8px;font-weight:700;color:#0b5f21;cursor:pointer;user-select:none}
.tab.active{background:#0b5f21;color:#fff}

/* Main layout */
#main{display:grid;grid-template-columns:320px 1fr;height:calc(100vh - 110px)}
#client-col{background:var(--bg);border-right:1px solid var(--line);overflow:auto}
#chat-col{background:var(--bg);display:flex;flex-direction:column;position:relative}

/* Client list */
.client-item{display:flex;justify-content:space-between;align-items:center;gap:10px;
  padding:12px;margin:10px;border:1px solid var(--line);border-radius:12px;background:#fff;
  box-shadow:0 1px 10px rgba(0,0,0,.05);cursor:pointer;transition:.15s}
.client-item:hover{transform:translateY(-1px);box-shadow:0 4px 18px rgba(0,0,0,.08);background:#fbfffb}
.client-name{font-weight:800}
.client-email{font-size:12px;color:#0d7f2f}
.client-assign{font-size:12px;color:#666}
.pill{border:none;border-radius:999px;padding:6px 11px;font-weight:700;color:#fff;cursor:pointer}
.pill.green{background:#14a83b}.pill.red{background:#d63a3a}.pill.gray{background:#888;cursor:not-allowed}

/* Chat */
#chat-head{background:#0aa835;color:#fff;padding:10px 16px;font-weight:800}
#messages{flex:1;overflow:auto;padding:20px 16px;scroll-behavior:smooth;position:relative}
#messages::before{
  content:"";position:absolute;left:50%;top:50%;width:520px;height:520px;opacity:.06;
  background:url('<?= $logoPath ?>') center/contain no-repeat;transform:translate(-50%,-50%);pointer-events:none}
.lock-banner{background:#fff3cd;color:#856404;border:1px solid #ffeeba;border-radius:10px;padding:10px 12px;margin:12px 16px;display:none}
.msg{max-width:70%;margin:10px 0;position:relative;clear:both}
.msg .bubble{padding:12px 14px;border-radius:16px;box-shadow:0 1px 6px rgba(0,0,0,.08);position:relative}
.msg.client{float:left}.msg.client .bubble{background:var(--client)}
.msg.csr{float:right}.msg.csr .bubble{background:var(--csr)}
.msg .meta{font-size:11px;color:#666;margin-top:6px}
.msg.client .bubble::after{content:"";position:absolute;left:-8px;bottom:10px;border:8px solid transparent;border-right-color:var(--client)}
.msg.csr .bubble::after{content:"";position:absolute;right:-8px;bottom:10px;border:8px solid transparent;border-left-color:var(--csr)}

/* Input */
#input{border-top:1px solid var(--line);padding:10px;display:flex;gap:10px;background:#fff}
#input input{flex:1;padding:12px;border:1px solid #bbb;border-radius:12px;font-size:14px}
#input button{padding:12px 18px;border:none;border-radius:12px;background:#0aa835;color:#fff;font-weight:800;cursor:pointer}
#input input:disabled,#input button:disabled{opacity:.5;cursor:not-allowed}

/* Floating info button (circle, no bg) */
#infoBtn{
  position:absolute;right:14px;top:50%;transform:translateY(-50%);
  width:42px;height:42px;border-radius:50%;border:2px solid rgba(0,0,0,.15);
  background:transparent;color:#0b5f21;font-size:20px;font-weight:900;
  display:flex;align-items:center;justify-content:center;cursor:pointer;
  backdrop-filter:saturate(1.2);box-shadow:0 2px 10px rgba(0,0,0,.08)
}

/* Right Drawer */
#drawer{
  position:absolute;top:0;right:0;height:100%;width:320px;background:#f7fff7;border-left:1px solid var(--line);
  transform:translateX(100%);transition:.25s;box-shadow:-8px 0 18px rgba(0,0,0,.08);z-index:2
}
#drawer.active{transform:translateX(0)}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;background:#eaffea;border-bottom:1px solid var(--line)}
.drawer-body{padding:12px 14px;overflow:auto;height:calc(100% - 50px)}
.dt{font-size:12px;color:#555;margin-top:4px}
.kv{margin:10px 0}.kv b{display:block;color:#0b5f21;margin-bottom:3px}
.note{background:#fff;border:1px dashed #9ed39e;border-radius:8px;padding:8px;margin-top:6px;color:#2f6b2f}

/* Reminders */
#reminders{display:none;flex-direction:column;height:100%}
#rem-filter{padding:10px;border-bottom:1px solid var(--line);background:#fbfffb}
#rem-list{padding:10px;overflow:auto}
.card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px;margin-bottom:10px;box-shadow:0 1px 8px rgba(0,0,0,.05)}
.badge{display:inline-block;font-size:12px;color:#fff;padding:4px 9px;border-radius:999px;margin:4px 6px 0 0}
.badge.upcoming{background:#ff9800}.badge.due{background:#e91e63}.badge.sent{background:#2196f3}

/* Responsive */
@media (max-width: 980px){
  #main{grid-template-columns:1fr}
  #client-col{height:40vh}
  #chat-col{height:60vh}
  #infoBtn{top:auto;bottom:14px;transform:none}
}
</style>
</head>
<body>
<div id="overlay" onclick="toggleSidebar(false)"></div>

<!-- Sidebar -->
<div id="sidebar">
  <h2>CSR Menu</h2>
  <a href="javascript:void(0)" onclick="switchTab('all')">💬 Chat Dashboard</a>
  <a href="javascript:void(0)" onclick="switchTab('mine')">👤 My Clients</a>
  <a href="javascript:void(0)" onclick="switchTab('rem')">⏰ Reminders</a>
  <a href="survey_responses.php">📝 Survey & Feedback</a>
  <a href="update_profile.php">👤 Edit Profile</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<header>
  <button id="hamb" onclick="toggleSidebar()">☰</button>
  <div class="brand">
    <img src="<?= $logoPath ?>" alt="Logo">
    <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
  </div>
</header>

<!-- Tabs -->
<div class="tabs">
  <div id="tab-all"  class="tab active" onclick="switchTab('all')">💬 All Clients</div>
  <div id="tab-mine" class="tab"         onclick="switchTab('mine')">👤 My Clients</div>
  <div id="tab-rem"  class="tab"         onclick="switchTab('rem')">⏰ Reminders</div>
  <div class="tab" onclick="location.href='survey_responses.php'">📝 Surveys</div>
  <div class="tab" onclick="location.href='update_profile.php'">👤 Edit Profile</div>
</div>

<!-- Main -->
<div id="main">
  <div id="client-col"></div>

  <div id="chat-col">
    <div id="chat-head"><span id="chat-title">Select a client to view messages</span></div>

    <div class="lock-banner" id="lockBanner">🔒 This client is assigned to another CSR. You cannot reply to this conversation.</div>

    <div id="messages"></div>

    <div id="input" style="display:none;">
      <input id="msg" placeholder="Type a reply…">
      <button id="sendBtn" onclick="sendMsg()">Send</button>
    </div>

    <!-- Floating info button (no background) -->
    <button id="infoBtn" title="Client details" onclick="toggleDrawer()">ⓘ</button>

    <!-- Right drawer -->
    <div id="drawer">
      <div class="drawer-head">
        <strong>Client Details</strong>
        <button class="pill gray" style="padding:4px 9px" onclick="toggleDrawer(false)">✕</button>
      </div>
      <div class="drawer-body" id="drawerBody">
        <div style="color:#888">No client selected.</div>
      </div>
    </div>

    <!-- Reminders panel -->
    <div id="reminders">
      <div id="rem-filter">
        <input id="rem-q" placeholder="Search name/email…" onkeyup="loadReminders()" style="padding:10px;border:1px solid #bbb;border-radius:8px;width:260px">
      </div>
      <div id="rem-list"></div>
    </div>
  </div>
</div>

<script>
let currentTab = 'all';
let currentClient = 0;
let currentAssignedTo = 'Unassigned';
const myUsername = <?= json_encode($csr_user) ?>;

/* Sidebar */
function toggleSidebar(force){
  const s = document.getElementById('sidebar');
  const o = document.getElementById('overlay');
  const open = s.classList.contains('active');
  const willOpen = (force === true) || (!open && force !== false);
  if (willOpen){ s.classList.add('active'); o.style.display='block'; }
  else { s.classList.remove('active'); o.style.display='none'; }
}

/* Tabs / Panels */
function setTabActive(id){
  ['tab-all','tab-mine','tab-rem'].forEach(t => document.getElementById(t).classList.remove('active'));
  if (document.getElementById('tab-'+id)) document.getElementById('tab-'+id).classList.add('active');
}
function showChatPane(show){
  document.getElementById('messages').style.display = show ? 'block' : 'none';
  document.getElementById('input').style.display    = (show && currentClient && currentAssignedTo === myUsername) ? 'flex' : (show ? 'flex' : 'none');
  document.getElementById('chat-head').style.display= show ? 'block' : 'none';
  // lock banner toggle
  document.getElementById('lockBanner').style.display = (show && currentClient && currentAssignedTo && currentAssignedTo !== 'Unassigned' && currentAssignedTo !== myUsername) ? 'block' : 'none';
  // disable inputs if locked
  const locked = (currentAssignedTo && currentAssignedTo !== 'Unassigned' && currentAssignedTo !== myUsername);
  document.getElementById('msg').disabled = locked;
  document.getElementById('sendBtn').disabled = locked;
}
function showRemindersPane(show){
  document.getElementById('reminders').style.display = show ? 'flex' : 'none';
}
function switchTab(tab){
  currentTab = (tab === 'rem') ? 'all' : tab;
  setTabActive(tab);

  if (tab === 'rem'){
    showChatPane(false);
    showRemindersPane(true);
    loadReminders();
  } else {
    showRemindersPane(false);
    showChatPane(true);
    loadClients();
  }
}

/* Clients */
function loadClients(){
  fetch('csr_dashboard.php?ajax=clients&tab='+currentTab)
    .then(r => r.text())
    .then(html => {
      const col = document.getElementById('client-col');
      col.innerHTML = html;
      col.querySelectorAll('.client-item').forEach(el => {
        el.addEventListener('click', () => selectClient(el));
      });
    });
}
function selectClient(el){
  currentClient = parseInt(el.dataset.id, 10);
  currentAssignedTo = el.dataset.csr || 'Unassigned';
  document.getElementById('chat-title').textContent = 'Chat with ' + el.dataset.name;
  showChatPane(true);
  loadChat();
  loadDrawer(currentClient); // refresh details
}

/* Chat */
function bubbleHTML(sender, name, text, tstamp){
  const who = sender === 'csr' ? 'csr' : 'client';
  const esc = s => s.replace(/[&<>"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
  return `
    <div class="msg ${who}">
      <div class="bubble"><strong>${esc(name)}:</strong> ${esc(text)}</div>
      <div class="meta">${new Date(tstamp).toLocaleString()}</div>
    </div>
  `;
}
function loadChat(){
  if(!currentClient) return;
  fetch('csr_dashboard.php?ajax=load_chat&client_id='+currentClient)
   .then(r => r.json())
   .then(rows => {
     const box = document.getElementById('messages');
     box.innerHTML = '';
     rows.forEach(m => {
       const name = (m.sender_type === 'csr') ? (m.csr_fullname || 'CSR') : (m.client_name || 'Client');
       box.insertAdjacentHTML('beforeend', bubbleHTML(m.sender_type, name, m.message, m.time));
     });
     box.scrollTop = box.scrollHeight;

     // if server-side assignment changed mid-session, respect it
     if (rows.length){
       const last = rows[rows.length-1];
       if (last.assigned_csr) {
         currentAssignedTo = last.assigned_csr;
         showChatPane(true);
       }
     }
   });
}
function sendMsg(){
  const input = document.getElementById('msg');
  const text = input.value.trim();
  if(!text || !currentClient) return;

  // Block if assigned to another CSR (defense-in-depth)
  if (currentAssignedTo && currentAssignedTo !== 'Unassigned' && currentAssignedTo !== myUsername) {
    alert('This client is assigned to '+currentAssignedTo+'. You cannot reply.');
    return;
  }

  const body = new URLSearchParams({sender_type:'csr', message:text, client_id:String(currentClient)});
  fetch('../SKYTRUFIBER/save_chat.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body
  }).then(() => { input.value=''; loadChat(); });
}
function assignClient(id){
  fetch('csr_dashboard.php?ajax=assign',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'client_id='+encodeURIComponent(id)
  }).then(r=>r.text()).then(t=>{
    if(t==='ok') loadClients();
    else if(t==='taken'){ alert('Already assigned to another CSR.'); loadClients(); }
  });
}
function unassignClient(id){
  if(!confirm('Unassign this client?')) return;
  fetch('csr_dashboard.php?ajax=unassign',{
    method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'client_id='+encodeURIComponent(id)
  }).then(()=>loadClients());
}

/* Right Drawer */
function toggleDrawer(force){
  const d = document.getElementById('drawer');
  const open = d.classList.contains('active');
  const willOpen = (force === true) || (!open && force !== false);
  if (willOpen){ d.classList.add('active'); loadDrawer(currentClient); }
  else { d.classList.remove('active'); }
}
function loadDrawer(cid){
  if(!cid){ document.getElementById('drawerBody').innerHTML='<div style="color:#888">No client selected.</div>'; return; }
  fetch('csr_dashboard.php?ajax=client_details&client_id='+cid)
    .then(r=>r.json())
    .then(d=>{
      const esc = s => (s||'').toString().replace(/[&<>"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
      const html = `
        <div class="kv"><b>Name</b>${esc(d.name||'')}</div>
        <div class="kv"><b>Email</b>${esc(d.email||'')}</div>
        <div class="kv"><b>Assigned CSR</b>${esc(d.assigned_csr||'Unassigned')}</div>
        <div class="kv"><b>Date Installed</b><span class="dt">${esc(d.date_installed||'—')}</span></div>
        <div class="kv"><b>Alerts / Notes</b>
          <div class="note">• Auto reminder preview available on the Reminders tab.</div>
          <div class="note">• Use Assign / Unassign to manage ownership.</div>
        </div>
      `;
      document.getElementById('drawerBody').innerHTML = html;
    });
}

/* Reminders */
function loadReminders(){
  const q = document.getElementById('rem-q').value.trim();
  fetch('csr_dashboard.php?ajax=reminders&q='+encodeURIComponent(q))
    .then(r => r.json())
    .then(list => {
      const box = document.getElementById('rem-list');
      box.innerHTML = '';
      if (!list.length){ box.innerHTML = '<div class="card">No upcoming reminders found.</div>'; return; }
      list.forEach(r => {
        let badges = '';
        r.banners.forEach(b => {
          const cls = (b.status === 'sent') ? 'sent' : (b.status === 'due' ? 'due' : 'upcoming');
          const txt = (b.type === '1_WEEK' ? '1 week' : '3 days') + ' — ' + (b.status === 'sent' ? 'Sent' : (b.status === 'due' ? 'Due Today' : 'Upcoming')) + ' ('+ b.date +')';
          badges += `<span class="badge ${cls}">${txt}</span>`;
        });
        box.insertAdjacentHTML('beforeend', `
          <div class="card">
            <div><strong>${r.name}</strong> &lt;${r.email}&gt;</div>
            <div>Cycle due: <b>${r.due}</b></div>
            <div style="margin-top:6px">${badges}</div>
          </div>
        `);
      });
    });
}

/* Init & Auto-refresh */
switchTab('all'); // default
setInterval(() => {
  if (document.getElementById('reminders').style.display !== 'none') loadReminders();
  if (document.getElementById('messages').style.display  !== 'none' && currentClient) loadChat();
}, 5000);
</script>
</body>
</html>
