<?php
/**
 * CSR Dashboard — SkyTruFiber (Option B)
 * - Left: Client list (with email)
 * - Center: Chat (chat bubbles, timestamps)
 * - Right: Details (Name, Email, Assigned CSR, Install date, Notes)
 * - Assign/Unassign
 * - Reminders preview banners (1 week / 3 days)
 * - Reply lock if the conversation is assigned to a different CSR
 */

session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) { header("Location: csr_login.php"); exit; }
$csr_user = $_SESSION['csr_user'];

$st = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username = :u LIMIT 1");
$st->execute([':u' => $csr_user]);
$csr = $st->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $csr['full_name'] ?? $csr_user;

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/*──────────────────────────────────────────────────────────────────────────────
| AJAX ENDPOINTS
|──────────────────────────────────────────────────────────────────────────────*/
if (isset($_GET['ajax'])) {

  /* ── Clients list (All / Mine) — includes email if matched in users table */
  if ($_GET['ajax'] === 'clients') {
    $tab = $_GET['tab'] ?? 'all';

    $sql = "
      SELECT 
        c.id, 
        c.name, 
        c.assigned_csr,
        COALESCE(
          (SELECT u.email FROM users u WHERE u.full_name = c.name LIMIT 1),
          ''
        ) AS email,
        MAX(ch.created_at) AS last_chat
      FROM clients c
      LEFT JOIN chat ch ON ch.client_id = c.id
    ";
    $where = ($tab === 'mine') ? " WHERE c.assigned_csr = :csr " : "";
    $sql .= $where . "
      GROUP BY c.id, c.name, c.assigned_csr
      ORDER BY last_chat DESC NULLS LAST, c.id DESC
    ";

    $st = $conn->prepare($sql);
    if ($tab === 'mine') $st->execute([':csr' => $csr_user]); else $st->execute();

    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
      $assigned = $row['assigned_csr'] ?: 'Unassigned';
      $owned    = ($assigned === $csr_user);

      if ($assigned === 'Unassigned') {
        $btn = "<button class='pill green' onclick='assignClient({$row['id']})' title='Assign to me'>＋</button>";
      } elseif ($owned) {
        $btn = "<button class='pill red' onclick='unassignClient({$row['id']})' title='Unassign'>−</button>";
      } else {
        $btn = "<button class='pill gray' disabled title='Assigned to another CSR'>🔒</button>";
      }

      $name   = htmlspecialchars($row['name']);
      $email  = htmlspecialchars($row['email'] ?? '');
      $assign = htmlspecialchars($assigned);

      echo "
        <div class='client-item' 
             data-id='{$row['id']}' 
             data-name='".htmlspecialchars($row['name'],ENT_QUOTES)."' 
             data-csr='".htmlspecialchars($assigned,ENT_QUOTES)."'>
          <div class='client-meta'>
            <div class='client-name'>{$name}</div>
            ".($email ? "<div class='client-email'>{$email}</div>" : "")."
            <div class='client-assign'>Assigned: {$assign}</div>
          </div>
          <div class='client-actions'>{$btn}</div>
        </div>
      ";
    }
    exit;
  }

  /* ── Load chat messages */
  if ($_GET['ajax'] === 'load_chat' && isset($_GET['client_id'])) {
    $cid = (int)$_GET['client_id'];
    $q = $conn->prepare("
      SELECT ch.message, ch.sender_type, ch.created_at, ch.assigned_csr, ch.csr_fullname, c.name AS client_name
      FROM chat ch 
      JOIN clients c ON c.id = ch.client_id
      WHERE ch.client_id = :cid
      ORDER BY ch.created_at ASC
    ");
    $q->execute([':cid' => $cid]);

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

  /* ── Load right-panel client info */
  if ($_GET['ajax'] === 'client_info' && isset($_GET['client_id'])) {
    $cid = (int)$_GET['client_id'];

    // From clients table (name, assigned_csr)
    $cst = $conn->prepare("SELECT id, name, assigned_csr FROM clients WHERE id = :id LIMIT 1");
    $cst->execute([':id' => $cid]);
    $client = $cst->fetch(PDO::FETCH_ASSOC);

    if (!$client) { echo json_encode(null); exit; }

    // Match user by full name for email & install date
    $ust = $conn->prepare("SELECT email, date_installed FROM users WHERE full_name = :n LIMIT 1");
    $ust->execute([':n' => $client['name']]);
    $u = $ust->fetch(PDO::FETCH_ASSOC);

    // Latest feedback as “notes”
    $nst = $conn->prepare("SELECT feedback FROM survey_responses WHERE client_name = :n ORDER BY created_at DESC LIMIT 1");
    $nst->execute([':n' => $client['name']]);
    $note = $nst->fetch(PDO::FETCH_ASSOC);

    $data = [
      'name'          => $client['name'],
      'email'         => $u['email'] ?? '',
      'assigned_csr'  => $client['assigned_csr'] ?: 'Unassigned',
      'date_installed'=> $u['date_installed'] ?? '',
      'notes'         => $note['feedback'] ?? '—'
    ];
    echo json_encode($data); exit;
  }

  /* ── Assign */
  if ($_GET['ajax'] === 'assign' && isset($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];

    $check = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
    $check->execute([':id' => $id]);
    $cur = $check->fetch(PDO::FETCH_ASSOC);

    if ($cur && $cur['assigned_csr'] && $cur['assigned_csr'] !== 'Unassigned') {
      echo 'taken'; exit;
    }

    $ok = $conn->prepare("UPDATE clients SET assigned_csr = :c WHERE id = :id")
               ->execute([':c' => $csr_user, ':id' => $id]);
    echo $ok ? 'ok' : 'fail'; exit;
  }

  /* ── Unassign */
  if ($_GET['ajax'] === 'unassign' && isset($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];
    $ok = $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned' WHERE id = :id AND assigned_csr = :c")
               ->execute([':id' => $id, ':c' => $csr_user]);
    echo $ok ? 'ok' : 'fail'; exit;
  }

  /* ── Reminders preview (no sending here) */
  if ($_GET['ajax'] === 'reminders') {
    $search = trim($_GET['q'] ?? '');
    $rows = [];
    $u = $conn->query("SELECT id, full_name, email, date_installed FROM users WHERE email IS NOT NULL ORDER BY full_name ASC")
              ->fetchAll(PDO::FETCH_ASSOC);
    $today = new DateTime('today');

    foreach ($u as $usr) {
      if (!$usr['date_installed']) continue;

      $di = new DateTime($usr['date_installed']);
      $dueDay = (int)$di->format('d');

      // compute due date (this month’s same day or next month)
      $base = new DateTime('first day of this month');
      $due  = (clone $base)->setDate((int)$base->format('Y'), (int)$base->format('m'), min($dueDay, 28));
      if ((int)$today->format('d') > (int)$due->format('d')) {
        $next = new DateTime('first day of next month');
        $due  = (clone $next)->setDate((int)$next->format('Y'), (int)$next->format('m'), min($dueDay, 28));
      }

      $oneWeek  = (clone $due)->modify('-7 days');
      $threeDay = (clone $due)->modify('-3 days');

      // already sent?
      $cycle = $due->format('Y-m-d');
      $st = $conn->prepare("
        SELECT reminder_type, status, sent_at
        FROM reminders
        WHERE client_id = :cid AND cycle_date = :cycle
      ");
      $st->execute([':cid' => $usr['id'], ':cycle' => $cycle]);
      $sent = $st->fetchAll(PDO::FETCH_ASSOC);

      $sentMap = [];
      foreach ($sent as $s) { $sentMap[$s['reminder_type']] = $s['status']; }

      $badges = [];
      if ($today <= $oneWeek && $today->diff($oneWeek)->days <= 7) {
        $badges[] = ['type' => '1_WEEK', 'status' => ($sentMap['1_WEEK'] ?? '') === 'sent' ? 'sent' : 'upcoming', 'date' => $oneWeek->format('Y-m-d')];
      } elseif ($today == $oneWeek) {
        $badges[] = ['type' => '1_WEEK', 'status' => ($sentMap['1_WEEK'] ?? '') === 'sent' ? 'sent' : 'due', 'date' => $oneWeek->format('Y-m-d')];
      }
      if ($today <= $threeDay && $today->diff($threeDay)->days <= 7) {
        $badges[] = ['type' => '3_DAYS', 'status' => ($sentMap['3_DAYS'] ?? '') === 'sent' ? 'sent' : 'upcoming', 'date' => $threeDay->format('Y-m-d')];
      } elseif ($today == $threeDay) {
        $badges[] = ['type' => '3_DAYS', 'status' => ($sentMap['3_DAYS'] ?? '') === 'sent' ? 'sent' : 'due', 'date' => $threeDay->format('Y-m-d')];
      }

      if (!$badges) continue;

      if ($search) {
        $hay = strtolower(($usr['full_name'] ?? '').' '.($usr['email'] ?? ''));
        if (strpos($hay, strtolower($search)) === false) continue;
      }

      $rows[] = [
        'user_id' => $usr['id'],
        'name'    => $usr['full_name'],
        'email'   => $usr['email'],
        'due'     => $due->format('Y-m-d'),
        'banners' => $badges
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
  --green:#009900; --green-600:#006b00; --green-700:#005c00;
  --light:#f6fff6; --soft:#eaffea; --bg:#ffffff; --line:#e6e6e6;
  --csr:#daf0ff; --client:#eaffea; --text:#1a1a1a;
}
*{box-sizing:border-box}
body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:var(--light);color:var(--text);overflow:hidden}

/* header */
header{
  height:60px;background:var(--green);color:#fff;display:flex;align-items:center;
  padding:0 16px;gap:12px;justify-content:space-between;font-weight:700
}
.brand{display:flex;align-items:center;gap:9px}
.brand img{height:38px}
#hamb{cursor:pointer;font-size:26px;background:none;border:none;color:#fff}

/* sidebar */
#overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;z-index:8}
#sidebar{
  position:fixed;top:0;left:0;width:260px;height:100vh;background:var(--green-600);
  color:#fff;transform:translateX(-100%);transition:.25s;z-index:9;
  box-shadow:5px 0 12px rgba(0,0,0,.2)
}
#sidebar.active{transform:translateX(0)}
#sidebar h2{margin:0;padding:18px;background:var(--green-700);text-align:center;font-size:18px}
#sidebar a{display:block;padding:14px 18px;text-decoration:none;color:#fff;font-weight:600}
#sidebar a:hover{background:#00a61b}

/* tabs */
.tabs{
  display:flex;gap:10px;background:var(--soft);padding:10px 14px;border-bottom:1px solid #cfe8cf
}
.tab{padding:9px 14px;border-radius:8px;font-weight:700;color:var(--green-600);cursor:pointer;user-select:none}
.tab.active{background:var(--green-600);color:#fff}

/* layout: left / center / right */
#main{
  display:grid;grid-template-columns:320px 1fr 280px;
  height:calc(100vh - 100px)
}
#client-col{background:var(--bg);border-right:1px solid var(--line);overflow:auto}
#chat-col{background:var(--bg);display:flex;flex-direction:column;position:relative}
#info-col{background:#fbfffb;border-left:1px solid var(--line);padding:12px;overflow:auto}

/* client list */
.client-item{
  display:flex;justify-content:space-between;align-items:center;gap:10px;
  padding:12px;margin:10px;border:1px solid var(--line);border-radius:10px;cursor:pointer;
  background:#fff;box-shadow:0 1px 6px rgba(0,0,0,.05)
}
.client-item:hover{background:#f5fff5}
.client-name{font-weight:800}
.client-email{font-size:12px;color:#087f23}
.client-assign{font-size:12px;color:#666}
.pill{border:none;border-radius:999px;padding:6px 11px;font-weight:700;color:#fff;cursor:pointer}
.pill.green{background:#14a83b}
.pill.red{background:#d63a3a}
.pill.gray{background:#888;cursor:not-allowed}

/* chat */
#chat-head{background:var(--green);color:#fff;padding:10px 16px;font-weight:800}
#messages{flex:1;overflow:auto;padding:18px 16px;scroll-behavior:smooth;position:relative}
#messages::before{
  content:"";position:absolute;left:50%;top:50%;width:520px;height:520px;opacity:.06;
  background:url('<?= $logoPath ?>') center/contain no-repeat;transform:translate(-50%,-50%);pointer-events:none
}
.msg{max-width:72%;margin:8px 0;position:relative;clear:both}
.msg .bubble{
  padding:12px 14px;border-radius:16px;box-shadow:0 1px 6px rgba(0,0,0,.08);position:relative
}
.msg.client .bubble{background:var(--client)}
.msg.csr    .bubble{background:var(--csr)}
.msg.client{float:left}
.msg.csr{float:right}
.msg .meta{font-size:11px;color:#666;margin-top:6px}
.msg.client .bubble::after{
  content:"";position:absolute;left:-8px;bottom:10px;border-width:8px;border-style:solid;
  border-color:transparent var(--client) transparent transparent
}
.msg.csr .bubble::after{
  content:"";position:absolute;right:-8px;bottom:10px;border-width:8px;border-style:solid;
  border-color:transparent transparent transparent var(--csr)
}

/* input */
#input{
  border-top:1px solid var(--line);padding:10px;display:flex;gap:10px;background:#fff
}
#input input{
  flex:1;padding:12px;border:1px solid #bbb;border-radius:10px;font-size:14px
}
#input button{
  padding:12px 18px;border:none;border-radius:10px;background:var(--green);color:#fff;font-weight:800;cursor:pointer
}

/* lock notice */
#lock-banner{
  display:none;background:#fff3cd;color:#8a6d3b;border:1px solid #ffeeba;border-left:none;border-right:none;
  padding:10px 14px;font-weight:600;text-align:center
}

/* reminders */
#reminders{display:none;flex-direction:column;height:100%}
#rem-filter{padding:10px;border-bottom:1px solid var(--line);background:#fbfffb}
#rem-list{padding:10px;overflow:auto}
.card{
  background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px;margin-bottom:10px;
  box-shadow:0 1px 6px rgba(0,0,0,.05)
}
.badge{
  display:inline-block;font-size:12px;color:#fff;padding:4px 9px;border-radius:999px;margin:4px 6px 0 0
}
.badge.upcoming{background:#ff9800}
.badge.due{background:#e91e63}
.badge.sent{background:#2196f3}

/* info panel (right column) */
.info-title{font-weight:800;color:#0b5f0b;margin:6px 0 10px}
.info-pair{margin:6px 0}
.info-key{font-size:12px;color:#666}
.info-val{font-weight:700}

/* responsive */
@media (max-width: 1180px){
  #main{grid-template-columns:320px 1fr}
  #info-col{display:none}
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
  <a href="survey_responses.php">📝 Survey Responses</a>
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

<!-- Main layout -->
<div id="main">
  <!-- LEFT -->
  <div id="client-col"></div>

  <!-- CENTER -->
  <div id="chat-col">
    <div id="chat-head"><span id="chat-title">Select a client to view messages</span></div>

    <div id="lock-banner">🔒 This conversation is assigned to another CSR. You can read but cannot reply.</div>

    <div id="messages"></div>

    <div id="input" style="display:none;">
      <input id="msg" placeholder="Type a reply…">
      <button onclick="sendMsg()">Send</button>
    </div>

    <!-- Reminders panel -->
    <div id="reminders">
      <div id="rem-filter">
        <input id="rem-q" placeholder="Search name/email…" onkeyup="loadReminders()" style="padding:10px;border:1px solid #bbb;border-radius:8px;width:260px">
      </div>
      <div id="rem-list"></div>
    </div>
  </div>

  <!-- RIGHT -->
  <div id="info-col">
    <div class="info-title">Client Details</div>
    <div class="info-pair"><div class="info-key">Name</div><div class="info-val" id="i-name">—</div></div>
    <div class="info-pair"><div class="info-key">Email</div><div class="info-val" id="i-email">—</div></div>
    <div class="info-pair"><div class="info-key">Assigned CSR</div><div class="info-val" id="i-assign">—</div></div>
    <div class="info-pair"><div class="info-key">Install Date</div><div class="info-val" id="i-install">—</div></div>
    <div class="info-title" style="margin-top:16px;">Notes</div>
    <div id="i-notes" style="white-space:pre-wrap;word-break:break-word;color:#333;">—</div>
  </div>
</div>

<script>
let currentTab   = 'all';
let currentClient= 0;
let currentCSR   = <?= json_encode($csr_user) ?>;

/* Sidebar — never auto-open on tab click */
function toggleSidebar(force){
  const s = document.getElementById('sidebar');
  const o = document.getElementById('overlay');
  const open = s.classList.contains('active');
  const willOpen = (force === true) || (!open && force !== false);
  if (willOpen){ s.classList.add('active');  o.style.display='block'; }
  else         { s.classList.remove('active'); o.style.display='none'; }
}

/* Tabs / Panels */
function setTabActive(id){
  ['tab-all','tab-mine','tab-rem'].forEach(t => document.getElementById(t).classList.remove('active'));
  const el = document.getElementById('tab-'+id);
  if (el) el.classList.add('active');
}
function showChatPane(show){
  document.getElementById('messages').style.display = show ? 'block' : 'none';
  document.getElementById('input').style.display    = (show && currentClient) ? 'flex' : 'none';
  document.getElementById('chat-head').style.display= show ? 'block' : 'none';
  document.getElementById('lock-banner').style.display = 'none';
}
function showRemindersPane(show){
  document.getElementById('reminders').style.display = show ? 'flex' : 'none';
}

function switchTab(tab){
  currentTab = (tab === 'rem') ? 'all' : tab;   // reminders uses all users for banners
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
  document.getElementById('chat-title').textContent = 'Chat with ' + el.dataset.name;
  showChatPane(true);
  // load chat + info
  loadChat();
  loadInfo();
}

/* Chat */
function bubbleHTML(sender, name, text, tstamp){
  const who = sender === 'csr' ? 'csr' : 'client';
  const safeName = name.replace(/[&<>"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
  const safeText = text.replace(/[&<>"]/g,  s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
  return `
    <div class="msg ${who}">
      <div class="bubble"><strong>${safeName}:</strong> ${safeText}</div>
      <div class="meta">${new Date(tstamp).toLocaleString()}</div>
    </div>
  `;
}

function setReplyLock(locked){
  const banner = document.getElementById('lock-banner');
  const input  = document.getElementById('input');
  const msg    = document.getElementById('msg');
  if (locked){
    banner.style.display = 'block';
    msg.disabled = true;
    input.style.opacity = .6;
  } else {
    banner.style.display = 'none';
    msg.disabled = false;
    input.style.opacity = 1;
  }
  // still show input row if a client is selected
  input.style.display = currentClient ? 'flex' : 'none';
}

function loadChat(){
  if(!currentClient) return;
  fetch('csr_dashboard.php?ajax=load_chat&client_id='+currentClient)
   .then(r => r.json())
   .then(rows => {
     const box = document.getElementById('messages');
     box.innerHTML = '';
     let assignedTo = null;
     rows.forEach(m => {
       const name = (m.sender_type === 'csr') ? (m.csr_fullname || 'CSR') : (m.client_name || 'Client');
       box.insertAdjacentHTML('beforeend', bubbleHTML(m.sender_type, name, m.message, m.time));
       // keep track of latest assignment value in the messages stream
       if (m.assigned_csr) assignedTo = m.assigned_csr;
     });
     box.scrollTop = box.scrollHeight;

     // Reply lock: if assigned to someone else (and not Unassigned)
     if (!assignedTo) {
       // fallback: peek current client info
       fetch('csr_dashboard.php?ajax=client_info&client_id='+currentClient)
         .then(r=>r.json()).then(info=>{
           const lock = info && info.assigned_csr && info.assigned_csr !== 'Unassigned' && info.assigned_csr !== currentCSR;
           setReplyLock(lock);
         });
     } else {
       const lock = (assignedTo !== 'Unassigned' && assignedTo !== currentCSR);
       setReplyLock(lock);
     }
   });
}

function sendMsg(){
  const input = document.getElementById('msg');
  const text = input.value.trim();
  if(!text || !currentClient || input.disabled) return;
  const body = new URLSearchParams({sender_type:'csr', message:text, client_id:String(currentClient)});
  fetch('../SKYTRUFIBER/save_chat.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body
  }).then(() => { input.value=''; loadChat(); });
}

/* Right info panel */
function loadInfo(){
  if(!currentClient) return;
  fetch('csr_dashboard.php?ajax=client_info&client_id='+currentClient)
    .then(r => r.json())
    .then(info => {
      document.getElementById('i-name').textContent    = info?.name || '—';
      document.getElementById('i-email').textContent   = info?.email || '—';
      document.getElementById('i-assign').textContent  = info?.assigned_csr || '—';
      document.getElementById('i-install').textContent = info?.date_installed || '—';
      document.getElementById('i-notes').textContent   = info?.notes || '—';
    });
}

/* Reminders (preview) */
function loadReminders(){
  const q = document.getElementById('rem-q').value.trim();
  fetch('csr_dashboard.php?ajax=reminders&q='+encodeURIComponent(q))
    .then(r => r.json())
    .then(list => {
      const box = document.getElementById('rem-list');
      box.innerHTML = '';
      if (!list.length){
        box.innerHTML = '<div class="card">No upcoming reminders found.</div>';
        return;
      }
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
switchTab('all');  // default landing
setInterval(() => {
  // only refresh visible panels
  if (document.getElementById('reminders').style.display !== 'none') loadReminders();
  if (document.getElementById('messages').style.display  !== 'none' && currentClient) loadChat();
}, 5000);
</script>
</body>
</html>
