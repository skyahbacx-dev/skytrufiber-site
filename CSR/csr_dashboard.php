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
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

// ✅ Logo fallback
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ===========================
   AJAX ENDPOINTS
   =========================== */
if (isset($_GET['ajax'])) {

  /* ---- Load Clients (All / Mine) ---- */
  if ($_GET['ajax'] === 'clients') {
    $tab = $_GET['tab'] ?? 'all';

    if ($tab === 'mine') {
      $stmt = $conn->prepare("
        SELECT c.id, c.name, c.assigned_csr, MAX(ch.created_at) AS last_chat
        FROM clients c
        LEFT JOIN chat ch ON ch.client_id = c.id
        WHERE c.assigned_csr = :csr
        GROUP BY c.id, c.name, c.assigned_csr
        ORDER BY last_chat DESC NULLS LAST, c.name ASC
      ");
      $stmt->execute([':csr' => $csr_user]);
    } else {
      $stmt = $conn->query("
        SELECT c.id, c.name, c.assigned_csr, MAX(ch.created_at) AS last_chat
        FROM clients c
        LEFT JOIN chat ch ON ch.client_id = c.id
        GROUP BY c.id, c.name, c.assigned_csr
        ORDER BY last_chat DESC NULLS LAST, c.name ASC
      ");
    }

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $assigned = $r['assigned_csr'] ?: 'Unassigned';
      $owned    = ($assigned === $csr_user);
      $btn = '';
      if ($assigned === 'Unassigned') {
        $btn = "<button class='assign-btn' title='Assign to me' onclick='assignClient({$r['id']})'>＋</button>";
      } elseif ($owned) {
        $btn = "<button class='unassign-btn' title='Unassign' onclick='unassignClient({$r['id']})'>−</button>";
      } else {
        $btn = "<button class='locked-btn' title='Assigned' disabled>🔒</button>";
      }

      echo "
        <div class='client-item' data-id='{$r['id']}' data-name='".htmlspecialchars($r['name'],ENT_QUOTES)."' data-csr='".htmlspecialchars($assigned,ENT_QUOTES)."'>
          <div class='client-label'>
            <strong>".htmlspecialchars($r['name'])."</strong>
            <small>Assigned: ".htmlspecialchars($assigned)."</small>
          </div>
          $btn
        </div>
      ";
    }
    exit;
  }

  /* ---- Assign Client ---- */
  if ($_GET['ajax'] === 'assign' && isset($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];
    $stmt = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $curr = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($curr && $curr['assigned_csr'] && $curr['assigned_csr'] !== 'Unassigned') {
      echo 'taken'; exit;
    }
    $up = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id");
    echo $up->execute([':csr' => $csr_user, ':id' => $id]) ? 'ok' : 'fail';
    exit;
  }

  /* ---- Unassign Client ---- */
  if ($_GET['ajax'] === 'unassign' && isset($_POST['client_id'])) {
    $id = (int)$_POST['client_id'];
    $up = $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned' WHERE id = :id AND assigned_csr = :csr");
    echo $up->execute([':id' => $id, ':csr' => $csr_user]) ? 'ok' : 'fail';
    exit;
  }

  /* ---- Load Reminders (with ETA if users.due_date exists) ---- */
  if ($_GET['ajax'] === 'reminders') {
    // We attempt to compute planned send date if `users.due_date` is present.
    // If not present, planned_at will be NULL and we just show Scheduled/Sent.
    $sql = "
      SELECT 
        r.id,
        r.client_id,
        r.csr_username,
        r.reminder_type,
        r.sent_at,
        r.status,
        c.name AS client_name,
        u.due_date,
        CASE
          WHEN r.reminder_type = '1_WEEK' AND u.due_date IS NOT NULL 
            THEN (u.due_date::timestamp - INTERVAL '7 days')
          WHEN r.reminder_type = '3_DAYS' AND u.due_date IS NOT NULL 
            THEN (u.due_date::timestamp - INTERVAL '3 days')
          ELSE NULL
        END AS planned_at
      FROM reminders r
      LEFT JOIN clients c ON c.id = r.client_id
      LEFT JOIN users   u ON LOWER(u.full_name) = LOWER(c.name)
      ORDER BY 
        /* Show scheduled soonest first, then sent desc */
        CASE WHEN r.sent_at IS NULL THEN 0 ELSE 1 END,
        COALESCE(
          CASE 
            WHEN r.reminder_type = '1_WEEK' AND u.due_date IS NOT NULL THEN (u.due_date::timestamp - INTERVAL '7 days')
            WHEN r.reminder_type = '3_DAYS' AND u.due_date IS NOT NULL THEN (u.due_date::timestamp - INTERVAL '3 days')
            ELSE NULL
          END,
          NOW()
        ) ASC,
        r.id DESC
    ";
    $stmt = $conn->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
  }

  /* ---- Create Reminder (optional, kept for future) ---- */
  if ($_GET['ajax'] === 'create_reminder' && !empty($_POST['client_id']) && !empty($_POST['type'])) {
    $cid = (int)$_POST['client_id'];
    $type = $_POST['type'];
    $ins = $conn->prepare("
      INSERT INTO reminders (client_id, csr_username, reminder_type, status)
      VALUES (:cid, :csr, :type, 'scheduled')
    ");
    $ok = $ins->execute([':cid'=>$cid, ':csr'=>$csr_user, ':type'=>$type]);
    echo $ok ? 'ok' : 'fail'; exit;
  }

  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>CSR Dashboard — SkyTruFiber</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
/* ——— Base ——— */
:root{
  --g-900:#006d00;
  --g-800:#008000;
  --g-700:#00a000;
  --g-600:#00b000;
  --g-100:#eaffea;
  --g-050:#f4fff4;
  --b-100:#f5f7fb;
  --line:#dfe7df;
  --text:#21312a;
  --muted:#667a6e;
  --white:#fff;
  --danger:#cc1a1a;
  --info:#0b74d6;
  --badge:#ffb100;
  --shadow:0 6px 18px rgba(0,0,0,.08);
}

*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;
  font-family:"Segoe UI",system-ui,-apple-system,Arial;
  color:var(--text);
  background:var(--g-050);
}

/* ——— Topbar ——— */
.topbar{
  height:56px;background:var(--g-800);color:var(--white);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 14px; box-shadow:var(--shadow); position:relative; z-index:3;
}
.brand{display:flex;align-items:center;gap:10px;font-weight:700}
.brand img{height:32px;filter:drop-shadow(0 2px 4px rgba(0,0,0,.3))}
.hamb{
  width:38px;height:38px;border-radius:10px;border:1px solid rgba(255,255,255,.25);
  display:grid;place-items:center;background:transparent;color:#fff;cursor:pointer;font-size:20px;
  transition:transform .2s ease;
}
.hamb.active{transform:rotate(90deg);}

/* ——— Layout grid ——— */
.wrapper{
  display:grid; grid-template-columns: 260px 340px 1fr;
  gap:12px; padding:12px;
}
@media(max-width:1200px){
  .wrapper{grid-template-columns: 240px 1fr; grid-template-areas:"sidebar content" "sidebar content";}
  .col-reminders{grid-column:2 / -1;}
  .col-right    {grid-column:2 / -1;}
}
@media(max-width:860px){
  .wrapper{grid-template-columns: 1fr;}
  .sidebar{position:fixed;top:56px;left:0;bottom:0;width:260px;transform:translateX(-100%);transition:.25s ease;z-index:4}
  .sidebar.show{transform:translateX(0)}
}

/* ——— Sidebar ——— */
.sidebar{
  background:var(--g-900); color:#fff; border-radius:14px; padding:10px; box-shadow:var(--shadow);
}
.side-title{
  display:flex;align-items:center;gap:8px;padding:10px;border-bottom:1px solid rgba(255,255,255,.15);font-weight:700
}
.side-link{
  display:flex;align-items:center;gap:10px;color:#fff;text-decoration:none;
  padding:10px 12px;border-radius:10px;margin-top:6px; font-weight:600;
}
.side-link:hover{background:rgba(255,255,255,.09)}
.side-link.active{background:rgba(255,255,255,.15)}

/* ——— Card / Panel ——— */
.card{
  background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow)
}
.section-head{
  background:var(--g-700); color:#fff; padding:10px 12px; font-weight:800;border-radius:14px 14px 0 0
}

/* ——— Reminders column ——— */
.rem-body{padding:12px;max-height:calc(100vh - 56px - 24px);overflow:auto}
.search{display:flex;gap:8px;margin-bottom:8px}
.search input{
  flex:1;border:1px solid var(--line);border-radius:10px;padding:10px 12px
}
.rem-item{
  border:1px solid var(--line);border-radius:12px;padding:10px 12px;margin-bottom:10px;
  background:var(--g-050)
}
.rem-top{display:flex;justify-content:space-between;align-items:center;gap:8px}
.badge{
  padding:3px 8px;border-radius:100px;font-size:12px;font-weight:800;color:#fff;white-space:nowrap
}
.badge-scheduled{background:var(--badge);color:#342c00}
.badge-sent{background:var(--g-700)}
.rem-meta{font-size:12px;color:var(--muted);margin-top:4px}
.rem-soon{background:#fff7e6;border:1px dashed #ffc866}
.rem-title{font-weight:700}

/* ——— Right Column (Clients + Chat) ——— */
.right-grid{
  display:grid;grid-template-columns: 320px 1fr; gap:12px; height:calc(100vh - 56px - 24px);
}
.list-panel{overflow:auto;border-right:1px dashed var(--line);padding:12px}
.client-item{
  display:flex;align-items:center;justify-content:space-between;gap:8px;
  border:1px solid var(--line);border-radius:12px;background:#fff;padding:10px 12px;margin-bottom:8px;cursor:pointer
}
.client-item:hover{background:var(--g-100)}
.client-label small{color:var(--muted);display:block;margin-top:2px}
.assign-btn,.unassign-btn,.locked-btn{
  border:none;border-radius:50%;width:28px;height:28px;color:#fff;cursor:pointer
}
.assign-btn{background:var(--g-700)}
.unassign-btn{background:var(--danger)}
.locked-btn{background:#888}

/* Chat */
.chat-wrap{display:flex;flex-direction:column;height:100%}
.chat-header{background:var(--g-700);color:#fff;padding:10px 12px;border-radius:14px 14px 0 0;font-weight:800}
.chat-body{position:relative;flex:1;overflow:auto;background:#fff;border-left:1px solid var(--line);border-right:1px solid var(--line)}
.chat-body::before{
  content:""; position:absolute; inset:0; background:url('<?= $logoPath ?>') no-repeat center 60%; 
  opacity:.06; background-size:560px auto; pointer-events:none;
}
.bubble{max-width:70%;padding:10px 12px;border-radius:12px;margin:8px 12px;font-size:14px;clear:both}
.bubble.client{background:#eaffea;float:left}
.bubble.csr{background:#e6f3ff;float:right}
.timestamp{display:block;font-size:11px;color:#777;margin-top:4px;text-align:right}
.chat-input{display:flex;gap:8px;border:1px solid var(--line);border-top:none;background:#fff;padding:10px;border-radius:0 0 14px 14px}
.chat-input input{flex:1;border:1px solid var(--line);padding:10px;border-radius:10px}
.chat-input button{background:var(--g-700);color:#fff;border:none;border-radius:10px;padding:10px 16px;font-weight:700;cursor:pointer}
.empty-hint{padding:24px;color:var(--muted)}
</style>
</head>
<body>

<!-- Top bar -->
<div class="topbar">
  <div class="brand">
    <button id="hamb" class="hamb" title="Toggle sidebar">☰</button>
    <img src="<?= $logoPath ?>" alt="Logo" />
    CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?>
  </div>
</div>

<div class="wrapper">
  <!-- Sidebar -->
  <aside id="sidebar" class="sidebar">
    <div class="side-title">Menu</div>
    <a class="side-link active" href="csr_dashboard.php?tab=all">💬 Chat Dashboard</a>
    <a class="side-link" href="csr_dashboard.php?tab=mine">👥 My Clients</a>
    <a class="side-link" href="#" onclick="scrollToRem();return false;">⏰ Reminders</a>
    <a class="side-link" href="survey_responses.php">📝 Survey Responses</a>
    <a class="side-link" href="csr_logout.php">🚪 Logout</a>
  </aside>

  <!-- Reminders column -->
  <section class="card col-reminders" id="remColumn">
    <div class="section-head">⏰ Upcoming Reminders</div>
    <div class="rem-body">
      <div class="search">
        <input id="remSearch" placeholder="Search reminders (client, type, status)…" oninput="filterReminders()" />
      </div>
      <div id="remList">Loading...</div>
    </div>
  </section>

  <!-- Right: client list + chat -->
  <section class="card col-right">
    <div class="right-grid">
      <div class="list-panel">
        <div class="section-head">Clients</div>
        <div id="clientList" style="padding-top:10px;"></div>
      </div>

      <div class="chat-wrap">
        <div class="chat-header"><span id="chatTitle">Select a client to view messages</span></div>
        <div id="messages" class="chat-body"></div>
        <div id="inputRow" class="chat-input" style="display:none;">
          <input id="msg" placeholder="Type a reply…" />
          <button onclick="sendMsg()">Send</button>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
const csrUser      = <?= json_encode($csr_user) ?>;
const csrFullname  = <?= json_encode($csr_fullname) ?>;

let currentTab='all';
let clientId=0;

// Sidebar toggle for small screens
const sidebar = document.getElementById('sidebar');
const hamb    = document.getElementById('hamb');
hamb.addEventListener('click', ()=>{
  hamb.classList.toggle('active');
  sidebar.classList.toggle('show');
});

// Scroll to reminders when clicked in sidebar
function scrollToRem(){
  document.getElementById('remColumn').scrollIntoView({behavior:'smooth',block:'start'});
}

/* ===========================
   LOAD CLIENTS
   =========================== */
function loadClients(){
  fetch('csr_dashboard.php?ajax=clients&tab='+currentTab)
    .then(r=>r.text())
    .then(html=>{
      const list=document.getElementById('clientList');
      list.innerHTML=html || '<div class="empty-hint">No clients yet.</div>';
      list.querySelectorAll('.client-item').forEach(el=>{
        el.addEventListener('click',()=>{
          selectClient(el);
        });
      });
    });
}
function selectClient(el){
  const assigned=el.dataset.csr;
  const name    =el.dataset.name;

  document.querySelectorAll('.client-item').forEach(i=>i.classList.remove('active'));
  el.classList.add('active');

  clientId=parseInt(el.dataset.id,10);
  document.getElementById('chatTitle').textContent='Chat with '+name;
  loadChat(assigned===csrUser,assigned);
}
function assignClient(id){
  fetch('csr_dashboard.php?ajax=assign',{method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'client_id='+encodeURIComponent(id)})
    .then(r=>r.text()).then(t=>{
      if(t==='ok'){loadClients()}
      else if(t==='taken'){alert('Client already assigned.')}
    });
}
function unassignClient(id){
  if(!confirm('Unassign this client?')) return;
  fetch('csr_dashboard.php?ajax=unassign',{method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'client_id='+encodeURIComponent(id)})
    .then(()=>loadClients());
}

/* ===========================
   CHAT
   =========================== */
function loadChat(isMine=false,assignedTo=''){
  if(!clientId) return;
  fetch('../SKYTRUFIBER/load_chat.php?client_id='+clientId)
    .then(r=>r.json()).then(list=>{
      const box=document.getElementById('messages');
      box.innerHTML='';
      if(!list.length){
        box.innerHTML='<div class="empty-hint">No messages yet.</div>';
      }
      list.forEach(m=>{
        const b=document.createElement('div');
        b.className='bubble '+(m.sender_type==='csr'?'csr':'client');
        const who=(m.sender_type==='csr')?(m.csr_fullname||m.assigned_csr||'CSR'):(m.client_name||'Client');
        b.textContent=who+': '+m.message;
        const t=document.createElement('span');t.className='timestamp';t.textContent=new Date(m.time).toLocaleString();
        b.appendChild(t);
        box.appendChild(b);
      });
      box.scrollTop=box.scrollHeight;
      document.getElementById('inputRow').style.display=(assignedTo===csrUser)?'flex':'none';
    });
}
function sendMsg(){
  const input=document.getElementById('msg');
  const text=input.value.trim();
  if(!text||!clientId) return;
  const body=new URLSearchParams();
  body.set('sender_type','csr');
  body.set('message',text);
  body.set('csr_user',csrUser);
  body.set('csr_fullname',csrFullname);
  body.set('client_id',String(clientId));

  fetch('../SKYTRUFIBER/save_chat.php',{method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
    .then(()=>{input.value='';loadChat(true);});
}

/* Live updates (SSE fallback to polling) */
(function live(){
  if(!!window.EventSource){
    const evt=new EventSource('../SKYTRUFIBER/realtime_updates.php');
    evt.addEventListener('update',()=>{
      if(clientId) loadChat();
      loadClients();
      loadReminders();
    });
  }else{
    setInterval(()=>{ if(clientId) loadChat(); loadClients(); loadReminders(); }, 4000);
  }
})();

/* ===========================
   REMINDERS
   =========================== */
let allReminders=[];
function loadReminders(){
  fetch('csr_dashboard.php?ajax=reminders')
    .then(r=>r.json())
    .then(rows=>{
      allReminders = rows || [];
      renderReminders(allReminders);
    })
    .catch(()=>{ document.getElementById('remList').innerHTML='<div class="empty-hint">No reminders.</div>'; });
}
function filterReminders(){
  const q=document.getElementById('remSearch').value.toLowerCase();
  const filtered=allReminders.filter(r=>{
    const text=[r.client_name||'', r.reminder_type||'', r.status||'', r.csr_username||''].join(' ').toLowerCase();
    return text.includes(q);
  });
  renderReminders(filtered);
}
function renderReminders(rows){
  const rem=document.getElementById('remList');
  if(!rows.length){ rem.innerHTML='<div class="empty-hint">No reminders.</div>'; return; }

  let html='';
  const now = new Date();

  rows.forEach(r=>{
    let badgeClass = (r.sent_at ? 'badge-sent' : 'badge-scheduled');
    let badgeText  = (r.sent_at ? 'Sent' : 'Scheduled');

    // Compute "soon" style if planned_at exists and is within next 7 days
    let soonClass = '';
    let metaLine2 = '';
    if(r.planned_at){
      const planned = new Date(r.planned_at);
      const ms=planned-now;
      const days=Math.floor(ms/86400000);
      if(ms>0 && days<=7 && !r.sent_at){
        soonClass=' rem-soon';
      }
      metaLine2 = `<div>Planned: <strong>${planned.toLocaleString()}</strong>${
        r.due_date ? ` • Due: <strong>${new Date(r.due_date).toLocaleDateString()}</strong>` : ''
      }</div>`;
    }else{
      if(r.due_date){
        metaLine2 = `<div>Due: <strong>${new Date(r.due_date).toLocaleDateString()}</strong></div>`;
      }
    }

    const sentLine = r.sent_at ? `<div>Sent: <strong>${new Date(r.sent_at).toLocaleString()}</strong></div>` : '';

    html += `
      <div class="rem-item${soonClass}">
        <div class="rem-top">
          <div class="rem-title">${escapeHTML(r.client_name || '—')}</div>
          <span class="badge ${badgeClass}">${badgeText}</span>
        </div>
        <div class="rem-meta">
          <div>Type: <strong>${escapeHTML(r.reminder_type || '')}</strong> • CSR: <strong>${escapeHTML(r.csr_username || '')}</strong></div>
          ${metaLine2}
          ${sentLine}
        </div>
      </div>
    `;
  });
  rem.innerHTML=html;
}
function escapeHTML(s){return (s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}

/* ===========================
   INIT
   =========================== */
window.onload=()=>{
  const params=new URLSearchParams(window.location.search);
  const tab=params.get('tab');
  if(tab==='mine'){currentTab='mine'}
  loadClients();
  loadReminders();
};
</script>
</body>
</html>
