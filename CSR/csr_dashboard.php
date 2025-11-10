<?php
/**
 * CSR Dashboard — SkyTruFiber (All-in-one file)
 * - Collapsible right chat column (round "…" button at top-right)
 * - Tabs: All Clients | My Clients | Reminders | Surveys | Edit Profile
 * - Sidebar is collapsible and DOES NOT auto-open on tab clicks
 * - Client list shows email (pulled from users by full_name)
 * - Aesthetic chat bubbles + redesigned chat header with aligned (i) icon
 * - Reply is disabled if a client is assigned to a different CSR
 * - Reminders preview: 1 week / 3 days banners; shows “Sent” if in reminders table
 * - Simple avatar logic:
 *     * Try to read gender from users (female -> penguin, male -> lion)
 *     * Fallback guess by first name; else initials badge
 */

session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
  header("Location: csr_login.php");
  exit;
}
$csr_user = $_SESSION['csr_user'];

$st = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username = :u LIMIT 1");
$st->execute([':u' => $csr_user]);
$csr = $st->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $csr['full_name'] ?? $csr_user;

// Logo
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ────────────────────────────────────────────────────────────────────────────
| AJAX ENDPOINTS
|──────────────────────────────────────────────────────────────────────────── */
if (isset($_GET['ajax'])) {

  // ── Clients list (All / Mine) — includes email (users table) + quick assigned flag
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

    $stc = $conn->prepare($sql);
    if ($tab === 'mine') $stc->execute([':csr' => $csr_user]); else $stc->execute();

    while ($row = $stc->fetch(PDO::FETCH_ASSOC)) {
      $assigned = $row['assigned_csr'] ?: 'Unassigned';
      $owned    = ($assigned === $csr_user);
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
        <div class='client-item' data-id='{$row['id']}' data-name='".htmlspecialchars($row['name'],ENT_QUOTES)."' data-csr='".htmlspecialchars($assigned,ENT_QUOTES)."'>
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

  // ── Load chat messages
  if ($_GET['ajax'] === 'load_chat' && isset($_GET['client_id'])) {
    $cid = (int)$_GET['client_id'];
    $q = $conn->prepare("
      SELECT ch.message, ch.sender_type, ch.created_at, ch.assigned_csr, ch.csr_fullname, c.name AS client_name
      FROM chat ch JOIN clients c ON c.id = ch.client_id
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

  // ── Client profile for avatar/email/gender (from users table by full_name)
  if ($_GET['ajax'] === 'client_profile' && isset($_GET['name'])) {
    $name = trim($_GET['name']);
    $ps = $conn->prepare("SELECT email, gender FROM users WHERE full_name = :n LIMIT 1");
    $ps->execute([':n' => $name]);
    $u = $ps->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
      'email'  => $u['email']  ?? null,
      'gender' => $u['gender'] ?? null,
    ]);
    exit;
  }

  // ── Assign & Unassign
  if ($_GET['ajax'] === 'assign' && isset($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];
    $check = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
    $check->execute([':id' => $id]);
    $cur = $check->fetch(PDO::FETCH_ASSOC);
    if ($cur && $cur['assigned_csr'] && $cur['assigned_csr'] !== 'Unassigned') { echo 'taken'; exit; }
    $ok = $conn->prepare("UPDATE clients SET assigned_csr = :c WHERE id = :id")
               ->execute([':c' => $csr_user, ':id' => $id]);
    echo $ok ? 'ok' : 'fail'; exit;
  }
  if ($_GET['ajax'] === 'unassign' && isset($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];
    $ok = $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned' WHERE id = :id AND assigned_csr = :c")
               ->execute([':id' => $id, ':c' => $csr_user]);
    echo $ok ? 'ok' : 'fail'; exit;
  }

  // ── Reminders preview
  if ($_GET['ajax'] === 'reminders') {
    $search = trim($_GET['q'] ?? '');
    $rows = [];
    $u = $conn->query("SELECT id, full_name, email, date_installed FROM users WHERE email IS NOT NULL ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $today = new DateTime('today');

    foreach ($u as $usr) {
      if (!$usr['date_installed']) continue;

      $di = new DateTime($usr['date_installed']);
      $dueDay = (int)$di->format('d');

      $base = new DateTime('first day of this month');
      $due  = (clone $base)->setDate((int)$base->format('Y'), (int)$base->format('m'), min($dueDay, 28));
      if ((int)$today->format('d') > (int)$due->format('d')) {
        $base->modify('first day of next month');
        $due  = (clone $base)->setDate((int)$base->format('Y'), (int)$base->format('m'), min($dueDay, 28));
      }

      $oneWeek  = (clone $due)->modify('-7 days');
      $threeDay = (clone $due)->modify('-3 days');

      $cycle = $due->format('Y-m-d');
      $st = $conn->prepare("SELECT reminder_type, status FROM reminders WHERE client_id = :cid AND cycle_date = :cycle");
      $st->execute([':cid' => $usr['id'], ':cycle' => $cycle]);
      $sentMap = [];
      foreach ($st as $s) { $sentMap[$s['reminder_type']] = $s['status']; }

      $badges = [];
      // 1 week
      if ($today <= $oneWeek && $today->diff($oneWeek)->days <= 7) {
        $badges[] = ['type'=>'1_WEEK','status'=>($sentMap['1_WEEK']??'')==='sent'?'sent':'upcoming','date'=>$oneWeek->format('Y-m-d')];
      } elseif ($today == $oneWeek) {
        $badges[] = ['type'=>'1_WEEK','status'=>($sentMap['1_WEEK']??'')==='sent'?'sent':'due','date'=>$oneWeek->format('Y-m-d')];
      }
      // 3 days
      if ($today <= $threeDay && $today->diff($threeDay)->days <= 7) {
        $badges[] = ['type'=>'3_DAYS','status'=>($sentMap['3_DAYS']??'')==='sent'?'sent':'upcoming','date'=>$threeDay->format('Y-m-d')];
      } elseif ($today == $threeDay) {
        $badges[] = ['type'=>'3_DAYS','status'=>($sentMap['3_DAYS']??'')==='sent'?'sent':'due','date'=>$threeDay->format('Y-m-d')];
      }

      if (!$badges) continue;

      $needle = strtolower($search);
      if ($needle) {
        $hay = strtolower(($usr['full_name'] ?? '').' '.($usr['email'] ?? ''));
        if (strpos($hay, $needle) === false) continue;
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
  --green:#0aa05b; --green-600:#07804a; --green-700:#056b3d;
  --soft:#eefcf4; --light:#f6fff9; --bg:#ffffff; --line:#e7efe9;
  --csr:#e6f2ff; --client:#ecfff1; --text:#142015; --shadow:0 8px 24px rgba(0,0,0,.08);
}
*{box-sizing:border-box}
body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:var(--light);color:var(--text);overflow:hidden}

/* Header */
header{
  height:64px;background:linear-gradient(135deg,#0fb572,#0aa05b);
  color:#fff;display:flex;align-items:center;justify-content:space-between;
  padding:0 16px;box-shadow:var(--shadow)
}
.brand{display:flex;align-items:center;gap:10px}
.brand img{height:40px;border-radius:10px}
#hamb{cursor:pointer;font-size:26px;background:none;border:none;color:#fff}

/* Sidebar */
#overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;z-index:8}
#sidebar{
  position:fixed;top:0;left:0;width:260px;height:100vh;background:var(--green-600);
  color:#fff;transform:translateX(-100%);transition:.25s;z-index:9;box-shadow:var(--shadow)
}
#sidebar.active{transform:translateX(0)}
#sidebar h2{margin:0;padding:18px;background:var(--green-700);text-align:center;font-size:18px}
#sidebar a{display:block;padding:14px 18px;text-decoration:none;color:#fff;font-weight:600}
#sidebar a:hover{background:#12c474}

/* Tabs */
.tabs{
  display:flex;gap:10px;background:var(--soft);padding:10px 14px;border-bottom:1px solid var(--line)
}
.tab{padding:9px 14px;border-radius:999px;font-weight:700;color:var(--green-600);cursor:pointer;user-select:none;background:#fff;border:1px solid var(--line)}
.tab.active{background:var(--green-600);color:#fff;border-color:transparent}

/* Layout */
#main{display:grid;grid-template-columns:340px 1fr;height:calc(100vh - 112px)}
#client-col{background:var(--bg);border-right:1px solid var(--line);overflow:auto}
#chat-col{background:var(--bg);display:flex;flex-direction:column;position:relative}

/* Right column collapse — round "…" anchor */
#collapseBtn{
  position:absolute;top:14px;right:14px;z-index:2;
  width:36px;height:36px;border-radius:50%;border:1px solid var(--line);
  background:#fff;display:flex;align-items:center;justify-content:center;
  font-weight:900;cursor:pointer;box-shadow:var(--shadow)
}
#chat-col.collapsed{display:none} /* simplest collapse */

/* Client list */
.client-item{
  display:flex;justify-content:space-between;align-items:center;gap:12px;
  padding:12px;margin:12px;border:1px solid var(--line);border-radius:14px;cursor:pointer;
  background:#fff;box-shadow:0 4px 14px rgba(0,0,0,.04)
}
.client-item:hover{background:#f7fffb}
.client-name{font-weight:800}
.client-email{font-size:12px;color:#1b6b3c}
.client-assign{font-size:12px;color:#666}
.pill{border:none;border-radius:999px;padding:6px 11px;font-weight:700;color:#fff;cursor:pointer}
.pill.green{background:#19b66e}
.pill.red{background:#e55252}
.pill.gray{background:#8b94a1;cursor:not-allowed}

/* Chat header — aesthetic with (i) aligned on right */
#chat-head{
  background:linear-gradient(135deg,#0aa05b,#07804a);
  color:#fff;padding:12px 16px;font-weight:800;display:flex;align-items:center;justify-content:space-between
}
.chat-title{display:flex;align-items:center;gap:10px}
.avatar{
  width:36px;height:36px;border-radius:50%;overflow:hidden;background:#eaf7ef;display:flex;align-items:center;justify-content:center;font-weight:800;color:#07804a;border:2px solid rgba(255,255,255,.4)
}
.avatar img{width:100%;height:100%;object-fit:cover}
.info-dot{
  width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;
  background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.35);font-weight:900
}

/* Messages */
#messages{flex:1;overflow:auto;padding:18px 16px;position:relative}
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
.msg .meta{font-size:11px;color:#667a6b;margin-top:6px}
.msg.client .bubble::after{
  content:"";position:absolute;left:-8px;bottom:10px;border-width:8px;border-style:solid;
  border-color:transparent var(--client) transparent transparent
}
.msg.csr .bubble::after{
  content:"";position:absolute;right:-8px;bottom:10px;border-width:8px;border-style:solid;
  border-color:transparent transparent transparent var(--csr)
}

/* Input row */
#input{
  border-top:1px solid var(--line);padding:10px;display:flex;gap:10px;background:#fff
}
#input input{
  flex:1;padding:12px;border:1px solid #cddad0;border-radius:12px;font-size:14px
}
#input button{
  padding:12px 18px;border:none;border-radius:12px;background:#0aa05b;color:#fff;font-weight:800;cursor:pointer
}
#input.disabled{opacity:.6;pointer-events:none}

/* Reminders */
#reminders{display:none;flex-direction:column;height:100%}
#rem-filter{padding:10px;border-bottom:1px solid var(--line);background:#fbfffb}
#rem-list{padding:10px;overflow:auto}
.card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px;margin-bottom:10px;box-shadow:0 2px 10px rgba(0,0,0,.04)}
.badge{display:inline-block;font-size:12px;color:#fff;padding:4px 9px;border-radius:999px;margin:4px 6px 0 0}
.badge.upcoming{background:#ff9800}
.badge.due{background:#e91e63}
.badge.sent{background:#2196f3}

/* Responsive */
@media (max-width: 980px){
  #main{grid-template-columns:1fr}
  #client-col{height:40vh}
  #chat-col{height:60vh}
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
  <button id="hamb" onclick="toggleSidebar()"><?=$nbsp='';?>☰</button>
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
  <!-- LEFT: Clients -->
  <div id="client-col"></div>

  <!-- RIGHT: Chat -->
  <div id="chat-col">
    <button id="collapseBtn" title="Hide chat" onclick="toggleRight()">&hellip;</button>

    <div id="chat-head">
      <div class="chat-title">
        <div class="avatar" id="chatAvatar"><!-- avatar here --></div>
        <div><span id="chat-title">Select a client</span></div>
      </div>
      <div class="info-dot" title="Conversation info">i</div>
    </div>

    <div id="messages"></div>

    <div id="input" style="display:none;">
      <input id="msg" placeholder="Type a reply…">
      <button onclick="sendMsg()">Send</button>
    </div>

    <!-- Reminders panel (hidden until tab) -->
    <div id="reminders">
      <div id="rem-filter">
        <input id="rem-q" placeholder="Search name/email…" onkeyup="loadReminders()" style="padding:10px;border:1px solid #cddad0;border-radius:12px;width:260px">
      </div>
      <div id="rem-list"></div>
    </div>
  </div>
</div>

<script>
let currentTab = 'all';
let currentClient = 0;
let currentClientAssignee = '';  // who owns this client
const me = <?= json_encode($csr_user) ?>;

/* Sidebar */
function toggleSidebar(force){
  const s = document.getElementById('sidebar');
  const o = document.getElementById('overlay');
  const open = s.classList.contains('active');
  const willOpen = (force === true) || (!open && force !== false);
  if (willOpen){ s.classList.add('active');  o.style.display='block'; }
  else         { s.classList.remove('active'); o.style.display='none'; }
}

/* Right chat collapse (round … button) */
function toggleRight(){
  const col = document.getElementById('chat-col');
  col.classList.toggle('collapsed');
}

/* Tabs */
function setTabActive(id){
  ['tab-all','tab-mine','tab-rem'].forEach(t => document.getElementById(t).classList.remove('active'));
  document.getElementById('tab-'+id).classList.add('active');
}
function showChatPane(show){
  document.getElementById('messages').style.display = show ? 'block' : 'none';
  document.getElementById('input').style.display    = (show && currentClient) ? 'flex' : 'none';
  document.getElementById('chat-head').style.display= show ? 'flex' : 'none';
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

/* Avatar helpers */
const penguinURL = 'https://raw.githubusercontent.com/google/material-design-icons/master/src/action/account_circle/materialicons/24px.svg'; // placeholder
const lionURL    = 'https://raw.githubusercontent.com/google/material-design-icons/master/src/action/face/materialicons/24px.svg';           // placeholder

function nameToInitials(n){
  if(!n) return '?';
  const parts = n.trim().split(/\s+/).slice(0,2);
  return parts.map(p => p[0]?.toUpperCase() || '').join('');
}
function setAvatar(name, gender){
  const slot = document.getElementById('chatAvatar');
  slot.innerHTML = '';
  if (gender === 'female'){
    const img = document.createElement('img'); img.src = penguinURL; slot.appendChild(img);
  } else if (gender === 'male'){
    const img = document.createElement('img'); img.src = lionURL; slot.appendChild(img);
  } else {
    slot.textContent = nameToInitials(name);
  }
}

/* Select client */
function selectClient(el){
  currentClient = parseInt(el.dataset.id, 10);
  currentClientAssignee = el.dataset.csr || 'Unassigned';
  const name = el.dataset.name;
  document.getElementById('chat-title').textContent = name;

  // Load avatar (gender from users if available)
  fetch('csr_dashboard.php?ajax=client_profile&name='+encodeURIComponent(name))
    .then(r => r.json())
    .then(p => setAvatar(name, (p && p.gender) ? p.gender.toLowerCase() : null))
    .catch(() => setAvatar(name, null));

  showChatPane(true);
  // Enable/disable input if not owned
  lockInputIfNotOwned();
  loadChat();
}

function lockInputIfNotOwned(){
  const inputRow = document.getElementById('input');
  if (!currentClient) { inputRow.style.display='none'; return; }
  const owned = (currentClientAssignee === 'Unassigned') || (currentClientAssignee === me);
  inputRow.classList.toggle('disabled', !owned);
  inputRow.style.display = 'flex';
}

/* Chat */
function esc(t){return t.replace(/[&<>"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));}
function bubbleHTML(sender, name, text, tstamp){
  const who = sender === 'csr' ? 'csr' : 'client';
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
   });
}
function sendMsg(){
  if(!currentClient) return;
  // block if assigned to another CSR
  if (currentClientAssignee && currentClientAssignee !== 'Unassigned' && currentClientAssignee !== me) {
    alert('This client is assigned to another CSR. You cannot reply.');
    return;
  }
  const input = document.getElementById('msg');
  const text = input.value.trim();
  if(!text) return;
  const body = new URLSearchParams({sender_type:'csr', message:text, client_id:String(currentClient)});
  fetch('../SKYTRUFIBER/save_chat.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body
  }).then(() => { input.value=''; loadChat(); });
}
function assignClient(id){
  fetch('csr_dashboard.php?ajax=assign', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'client_id='+encodeURIComponent(id)
  }).then(r=>r.text()).then(t => {
    if (t==='ok') loadClients();
    else if (t==='taken') { alert('Already assigned to another CSR.'); loadClients(); }
  });
}
function unassignClient(id){
  if(!confirm('Unassign this client?')) return;
  fetch('csr_dashboard.php?ajax=unassign', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'client_id='+encodeURIComponent(id)
  }).then(()=>loadClients());
}

/* Reminders */
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
            <div><strong>${esc(r.name)}</strong> &lt;${esc(r.email)}&gt;</div>
            <div>Cycle due: <b>${r.due}</b></div>
            <div style="margin-top:6px">${badges}</div>
          </div>
        `);
      });
    });
}

/* Init */
switchTab('all'); // default
setInterval(() => {
  if (document.getElementById('reminders').style.display !== 'none') loadReminders();
  if (document.getElementById('messages').style.display  !== 'none' && currentClient) loadChat();
}, 5000);
</script>
</body>
</html>
