<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Safe escaper that also handles nulls (prevents deprecation warnings)
function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$st = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$st->execute([':u'=>$csr_user]);
$row = $st->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ============================================================
   AJAX
   ============================================================ */
if (isset($_GET['ajax'])) {

    // Clients (all/mine)
    if ($_GET['ajax'] === 'clients') {
        $tab = $_GET['tab'] ?? 'all';

        $sql = "
          SELECT c.id, c.name, c.assigned_csr,
                 (SELECT email FROM users u WHERE u.full_name = c.name LIMIT 1) AS email,
                 MAX(ch.created_at) AS last_chat
          FROM clients c
          LEFT JOIN chat ch ON ch.client_id = c.id
        ";
        $where = ($tab==='mine') ? " WHERE c.assigned_csr = :csr " : "";
        $sql .= $where . " GROUP BY c.id ORDER BY last_chat DESC NULLS LAST";

        $q = $conn->prepare($sql);
        if ($tab==='mine') $q->execute([':csr'=>$csr_user]); else $q->execute();

        while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
            $assigned = $r['assigned_csr'] ?: 'Unassigned';
            $owned    = ($assigned === $csr_user);

            if ($assigned === 'Unassigned') {
                $btn = "<button class='pill green' onclick='assignClient({$r['id']})' title='Assign to me'>＋</button>";
            } elseif ($owned) {
                $btn = "<button class='pill red' onclick='unassignClient({$r['id']})' title='Unassign'>−</button>";
            } else {
                $btn = "<button class='pill gray' disabled title='Assigned to another CSR'>🔒</button>";
            }

            echo "
              <div class='client-item' data-id='{$r['id']}' data-name='".h($r['name'])."' data-csr='".h($assigned)."'>
                <div class='client-meta'>
                  <div class='client-name'>".h($r['name'])."</div>
                  ".($r['email'] ? "<div class='client-email'>".h($r['email'])."</div>" : "")."
                  <div class='client-assign'>Assigned: ".h($assigned)."</div>
                </div>
                <div class='client-actions'>{$btn}</div>
              </div>
            ";
        }
        exit;
    }

    // Chat messages
    if ($_GET['ajax']==='load_chat' && isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];
        $q = $conn->prepare("
          SELECT ch.message, ch.sender_type, ch.created_at, ch.csr_fullname, c.name AS client_name
          FROM chat ch JOIN clients c ON c.id=ch.client_id
          WHERE ch.client_id=:cid
          ORDER BY ch.created_at ASC
        ");
        $q->execute([':cid'=>$cid]);

        $out=[];
        while ($m=$q->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'message'      => $m['message'],
                'sender_type'  => $m['sender_type'],
                'time'         => $m['created_at'],
                'client_name'  => $m['client_name'],
                'csr_fullname' => $m['csr_fullname']
            ];
        }
        echo json_encode($out); exit;
    }

    // Client profile (email/gender) by full_name
    if ($_GET['ajax']==='client_profile' && isset($_GET['name'])) {
        $name = $_GET['name'];
        $ps = $conn->prepare("SELECT email, gender FROM users WHERE full_name=:n LIMIT 1");
        $ps->execute([':n'=>$name]);
        $u = $ps->fetch(PDO::FETCH_ASSOC) ?: [];
        echo json_encode($u); exit;
    }

    // Assign / Unassign
    if ($_GET['ajax']==='assign' && isset($_POST['client_id'])) {
        $id = (int)$_POST['client_id'];
        $c  = $conn->prepare("SELECT assigned_csr FROM clients WHERE id=:id");
        $c->execute([':id'=>$id]);
        $cur=$c->fetch(PDO::FETCH_ASSOC);
        if ($cur && $cur['assigned_csr'] && $cur['assigned_csr']!=='Unassigned') { echo 'taken'; exit; }

        $ok = $conn->prepare("UPDATE clients SET assigned_csr=:c WHERE id=:id")->execute([':c'=>$csr_user,':id'=>$id]);
        echo $ok?'ok':'fail'; exit;
    }
    if ($_GET['ajax']==='unassign' && isset($_POST['client_id'])) {
        $id=(int)$_POST['client_id'];
        $conn->prepare("UPDATE clients SET assigned_csr='Unassigned' WHERE id=:id AND assigned_csr=:c")
             ->execute([':id'=>$id, ':c'=>$csr_user]);
        echo 'ok'; exit;
    }

    // Reminders (preview banners)
    if ($_GET['ajax']==='reminders') {
        $search = strtolower(trim($_GET['q'] ?? ''));
        $today  = new DateTime('today');
        $rows   = [];

        $u = $conn->query("SELECT id, full_name, email, date_installed FROM users WHERE email IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($u as $usr) {
            if (!$usr['date_installed']) continue;

            $di     = new DateTime($usr['date_installed']);
            $dueDay = (int)$di->format('d');

            $base = new DateTime('first day of this month');
            $due  = (clone $base)->setDate((int)$base->format('Y'), (int)$base->format('m'), min($dueDay, 28));
            if ((int)$today->format('d') > (int)$due->format('d')) {
                $base->modify('first day of next month');
                $due = (clone $base)->setDate((int)$base->format('Y'), (int)$base->format('m'), min($dueDay, 28));
            }

            $oneW = (clone $due)->modify('-7 days');
            $three= (clone $due)->modify('-3 days');

            $cycle = $due->format('Y-m-d');
            $st = $conn->prepare("SELECT reminder_type,status FROM reminders WHERE client_id=:cid AND cycle_date=:cycle");
            $st->execute([':cid'=>$usr['id'], ':cycle'=>$cycle]);
            $sent=[];
            foreach($st as $r){ $sent[$r['reminder_type']]=$r['status']; }

            $labels=[];
            if ($today <= $oneW && $today->diff($oneW)->days <= 7) {
                $labels[] = ['type'=>'1_WEEK','status'=>($sent['1_WEEK']??'')==='sent'?'sent':'upcoming','date'=>$oneW->format('Y-m-d')];
            } elseif ($today == $oneW) {
                $labels[] = ['type'=>'1_WEEK','status'=>($sent['1_WEEK']??'')==='sent'?'sent':'due','date'=>$oneW->format('Y-m-d')];
            }

            if ($today <= $three && $today->diff($three)->days <= 7) {
                $labels[] = ['type'=>'3_DAYS','status'=>($sent['3_DAYS']??'')==='sent'?'sent':'upcoming','date'=>$three->format('Y-m-d')];
            } elseif ($today == $three) {
                $labels[] = ['type'=>'3_DAYS','status'=>($sent['3_DAYS']??'')==='sent'?'sent':'due','date'=>$three->format('Y-m-d')];
            }

            if (!$labels) continue;

            if ($search) {
                $hay = strtolower(($usr['full_name'] ?? '').' '.($usr['email'] ?? ''));
                if (strpos($hay, $search) === false) continue;
            }

            $rows[] = [
                'name'    => $usr['full_name'],
                'email'   => $usr['email'],
                'due'     => $due->format('Y-m-d'),
                'banners' => $labels
            ];
        }

        echo json_encode($rows); exit;
    }

    http_response_code(400); echo 'bad'; exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= h($csr_fullname) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="csr_dashboard.css">
</head>
<body>

<div id="overlay" onclick="toggleSidebar(false)"></div>

<div id="sidebar">
  <h2>CSR Menu</h2>
  <a href="javascript:void(0)" onclick="switchTab('all')">💬 All Clients</a>
  <a href="javascript:void(0)" onclick="switchTab('mine')">👤 My Clients</a>
  <a href="javascript:void(0)" onclick="switchTab('rem')">⏰ Reminders</a>
  <a href="survey_responses.php">📝 Survey Responses</a>
  <a href="update_profile.php">👤 Edit Profile</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<header>
  <button id="hamb" onclick="toggleSidebar()">☰</button>
  <div class="brand">
    <img src="<?= h($logoPath) ?>" alt="Logo">
    <span>CSR Dashboard — <?= h($csr_fullname) ?></span>
  </div>
</header>

<div class="tabs">
  <div id="tab-all"  class="tab active" onclick="switchTab('all')">💬 All Clients</div>
  <div id="tab-mine" class="tab"         onclick="switchTab('mine')">👤 My Clients</div>
  <div id="tab-rem"  class="tab"         onclick="switchTab('rem')">⏰ Reminders</div>
  <div class="tab" onclick="location.href='survey_responses.php'">📝 Surveys</div>
  <div class="tab" onclick="location.href='update_profile.php'">👤 Edit Profile</div>
</div>

<div id="main">
  <div id="client-col"></div>

  <div id="chat-col">
    <button id="collapseBtn" title="Toggle compact" onclick="toggleRight()">…</button>

    <div id="chat-head">
      <div class="chat-title">
        <div class="avatar" id="chatAvatar"></div>
        <div id="chat-title">Select a client</div>
      </div>
      <div class="info-dot" title="Conversation info">i</div>
    </div>

    <div id="messages"></div>

    <div id="input" style="display:none;">
      <input id="msg" placeholder="Type a reply…">
      <button onclick="sendMsg()">Send</button>
    </div>

    <div id="reminders" style="display:none;">
      <div style="padding:10px;border-bottom:1px solid #e7efe9;background:#fbfffb">
        <input id="rem-q" placeholder="Search name/email…" onkeyup="loadReminders()" style="padding:10px;border:1px solid #cddad0;border-radius:12px;width:260px">
      </div>
      <div id="rem-list"></div>
    </div>
  </div>
</div>

<script>
/* ===================== STATE ===================== */
let currentTab = 'all';
let currentClient = 0;
let currentClientAssignee = '';
const me = <?= json_encode($csr_user) ?>;

/* ===================== UTIL ===================== */
function esc(s){return (s??'').replace(/[&<>"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));}
function nameGuessGender(n){
  if(!n) return 'male';
  n = n.toLowerCase();
  if (/\b(macy|alicia|maria|anne|ella|ly)\b/.test(n) || n.endsWith('a')) return 'female';
  return 'male';
}

/* ===================== SIDEBAR ===================== */
function toggleSidebar(force){
  const s = document.getElementById('sidebar');
  const o = document.getElementById('overlay');
  const open = s.classList.contains('active');
  const willOpen = (force === true) || (!open && force !== false);
  if (willOpen){ s.classList.add('active');  o.style.display='block'; }
  else         { s.classList.remove('active'); o.style.display='none'; }
}

/* ===================== RIGHT COMPACT ===================== */
let compact=false;
function toggleRight(){
  compact = !compact;
  const col = document.getElementById('chat-col');
  const btn = document.getElementById('collapseBtn');
  if (compact){
    btn.textContent = 'i';
    col.style.maxWidth = '680px';
  } else {
    btn.textContent = '…';
    col.style.maxWidth = '';
  }
}

/* ===================== TABS ===================== */
function setTabActive(id){
  ['tab-all','tab-mine','tab-rem'].forEach(t=>{const el=document.getElementById(t); if(el) el.classList.remove('active');});
  const x = document.getElementById('tab-'+id);
  if (x) x.classList.add('active');
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
  currentTab = (tab==='rem') ? 'all' : tab; // reminders doesn't filter client list
  setTabActive(tab);
  if (tab==='rem'){
    showChatPane(false);
    showRemindersPane(true);
    loadReminders();
  } else {
    showRemindersPane(false);
    showChatPane(true);
    loadClients();
  }
}

/* ===================== CLIENTS ===================== */
function loadClients(){
  fetch('csr_dashboard.php?ajax=clients&tab='+encodeURIComponent(currentTab))
    .then(r=>r.text())
    .then(html=>{
      const col=document.getElementById('client-col');
      col.innerHTML = html;
      col.querySelectorAll('.client-item').forEach(el=>{
        el.addEventListener('click', ()=>selectClient(el));
      });
    });
}

function setAvatar(name, gender){
  const penguinIcon = "https://cdn-icons-png.flaticon.com/512/616/616490.png";
  const lionIcon    = "https://cdn-icons-png.flaticon.com/512/1998/1998610.png";
  const box = document.getElementById('chatAvatar');
  box.innerHTML='';
  const img=document.createElement('img');
  img.src = (gender==='female') ? penguinIcon : lionIcon;
  box.appendChild(img);
}

function selectClient(el){
  currentClient = parseInt(el.dataset.id,10);
  currentClientAssignee = el.dataset.csr || 'Unassigned';
  const name = el.dataset.name;
  document.getElementById('chat-title').textContent = name;
  // avatar
  fetch('csr_dashboard.php?ajax=client_profile&name='+encodeURIComponent(name))
    .then(r=>r.json())
    .then(p=>{
      const gender = (p && p.gender) ? String(p.gender).toLowerCase() : nameGuessGender(name);
      setAvatar(name, gender);
    })
    .catch(()=>setAvatar(name, nameGuessGender(name)));

  showChatPane(true);
  lockInputIfNotOwned();
  loadChat();
}

function lockInputIfNotOwned(){
  const inputRow = document.getElementById('input');
  if (!currentClient) { inputRow.style.display='none'; return; }
  const owned = (currentClientAssignee==='Unassigned' || currentClientAssignee===me);
  inputRow.style.opacity = owned ? '1' : '.55';
  inputRow.style.pointerEvents = owned ? 'auto' : 'none';
}

/* ===================== CHAT ===================== */
function bubbleHTML(sender, name, text, tstamp){
  const who = (sender==='csr') ? 'csr' : 'client';
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
    .then(r=>r.json())
    .then(list=>{
      const box=document.getElementById('messages');
      box.innerHTML='';
      list.forEach(m=>{
        const n = (m.sender_type==='csr') ? (m.csr_fullname || 'CSR') : (m.client_name || 'Client');
        box.insertAdjacentHTML('beforeend', bubbleHTML(m.sender_type, n, m.message, m.time));
      });
      box.scrollTop = box.scrollHeight;
    });
}

function sendMsg(){
  if(!currentClient) return;
  if (currentClientAssignee && currentClientAssignee!=='Unassigned' && currentClientAssignee!==me) {
    alert('This client is assigned to another CSR. You cannot reply.');
    return;
  }
  const input=document.getElementById('msg');
  const text=input.value.trim();
  if(!text) return;
  const body=new URLSearchParams({sender_type:'csr', message:text, client_id:String(currentClient)});
  fetch('../SKYTRUFIBER/save_chat.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
    .then(()=>{input.value='';loadChat();});
}

/* ===================== ASSIGN ===================== */
function assignClient(id){
  fetch('csr_dashboard.php?ajax=assign',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'client_id='+encodeURIComponent(id)})
    .then(r=>r.text()).then(t=>{
      if(t==='taken'){ alert('Already assigned to another CSR.'); }
      loadClients();
    });
}
function unassignClient(id){
  if(!confirm('Unassign this client?')) return;
  fetch('csr_dashboard.php?ajax=unassign',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'client_id='+encodeURIComponent(id)})
    .then(()=>loadClients());
}

/* ===================== REMINDERS ===================== */
function loadReminders(){
  const q=document.getElementById('rem-q').value.trim();
  fetch('csr_dashboard.php?ajax=reminders&q='+encodeURIComponent(q))
    .then(r=>r.json())
    .then(rows=>{
      const box=document.getElementById('rem-list');
      box.innerHTML='';
      if(!rows.length){ box.innerHTML='<div class="card">No upcoming reminders found.</div>'; return; }
      rows.forEach(r=>{
        let badges='';
        r.banners.forEach(b=>{
          const cls = (b.status==='sent') ? 'sent' : (b.status==='due'?'due':'upcoming');
          const txt = (b.type==='1_WEEK'?'1 week':'3 days')+' — '+(b.status==='sent'?'Sent':(b.status==='due'?'Due Today':'Upcoming'))+' ('+b.date+')';
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

/* ===================== INIT + POLL ===================== */
switchTab('all');
setInterval(()=>{
  if (document.getElementById('reminders').style.display !== 'none') loadReminders();
  if (document.getElementById('messages').style.display  !== 'none' && currentClient) loadChat();
}, 5000);
</script>
</body>
</html>
