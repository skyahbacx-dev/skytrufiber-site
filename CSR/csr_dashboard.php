<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) { header("Location: csr_login.php"); exit; }
$csr_user = $_SESSION['csr_user'];

$st = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username=:u LIMIT 1");
$st->execute([':u'=>$csr_user]);
$csr = $st->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $csr['full_name'] ?? $csr_user;

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

if (isset($_GET['ajax'])) {

  // CLIENTS LIST (with email from users when possible)
  if ($_GET['ajax']==='clients') {
    $tab = $_GET['tab'] ?? 'all';
    $sql = "
      SELECT c.id, c.name,
             (SELECT email FROM users u WHERE u.full_name = c.name LIMIT 1) AS email,
             c.assigned_csr,
             MAX(ch.created_at) AS last_chat
        FROM clients c
        LEFT JOIN chat ch ON ch.client_id = c.id
    ";
    $where = ($tab==='mine') ? " WHERE c.assigned_csr = :csr " : "";
    $sql .= $where . " GROUP BY c.id, c.name, c.assigned_csr ORDER BY last_chat DESC NULLS LAST";
    $st = $conn->prepare($sql);
    if ($tab==='mine') $st->execute([':csr'=>$csr_user]); else $st->execute();

    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
      $assigned = $row['assigned_csr'] ?: 'Unassigned';
      $owned    = ($assigned === $csr_user);
      $btn = $assigned==='Unassigned'
        ? "<button class='assign' onclick='assignClient({$row['id']})'>＋</button>"
        : ($owned ? "<button class='unassign' onclick='unassignClient({$row['id']})'>−</button>" : "<button class='lock' disabled>🔒</button>");

      $email = htmlspecialchars($row['email'] ?? '', ENT_QUOTES);
      echo "
        <div class='client-item' data-id='{$row['id']}' data-name='".htmlspecialchars($row['name'],ENT_QUOTES)."' data-csr='".htmlspecialchars($assigned,ENT_QUOTES)."'>
          <div>
            <strong>".htmlspecialchars($row['name'])."</strong>
            ".($email ? "<div class='client-email'>{$email}</div>" : "")."
            <div class='assigned'>Assigned: ".htmlspecialchars($assigned)."</div>
          </div>
          <div class='actions'>{$btn}</div>
        </div>
      ";
    }
    exit;
  }

  // LOAD CHAT (for selected client)
  if ($_GET['ajax']==='load_chat' && isset($_GET['client_id'])) {
    $cid=(int)$_GET['client_id'];
    $q=$conn->prepare("
      SELECT ch.message, ch.sender_type, ch.created_at, ch.assigned_csr, ch.csr_fullname, c.name AS client_name
      FROM chat ch JOIN clients c ON c.id=ch.client_id
      WHERE ch.client_id=:cid ORDER BY ch.created_at ASC
    ");
    $q->execute([':cid'=>$cid]);
    $rows=[];
    while($r=$q->fetch(PDO::FETCH_ASSOC)){
      $rows[]=[
        'message'=>$r['message'],
        'sender_type'=>$r['sender_type'],
        'time'=>date('Y-m-d H:i:s',strtotime($r['created_at'])),
        'client_name'=>$r['client_name'],
        'assigned_csr'=>$r['assigned_csr'],
        'csr_fullname'=>$r['csr_fullname']
      ];
    }
    echo json_encode($rows); exit;
  }

  // ASSIGN / UNASSIGN
  if ($_GET['ajax']==='assign' && isset($_POST['client_id'])) {
    $id=(int)$_POST['client_id'];
    $r=$conn->prepare("SELECT assigned_csr FROM clients WHERE id=:id")->execute([':id'=>$id]);
    $chk=$conn->query("SELECT assigned_csr FROM clients WHERE id={$id}")->fetch(PDO::FETCH_ASSOC);
    if ($chk && $chk['assigned_csr'] && $chk['assigned_csr']!=='Unassigned'){echo 'taken';exit;}
    $ok=$conn->prepare("UPDATE clients SET assigned_csr=:c WHERE id=:id")->execute([':c'=>$csr_user,':id'=>$id]);
    echo $ok?'ok':'fail'; exit;
  }
  if ($_GET['ajax']==='unassign' && isset($_POST['client_id'])) {
    $id=(int)$_POST['client_id'];
    $ok=$conn->prepare("UPDATE clients SET assigned_csr='Unassigned' WHERE id=:id AND assigned_csr=:c")->execute([':id'=>$id,':c'=>$csr_user]);
    echo $ok?'ok':'fail'; exit;
  }

  // REMINDERS: list/search banners (upcoming & sent)
  if ($_GET['ajax']==='reminders') {
    $search = "%".($_GET['q']??'')."%";

    // Compute upcoming for all users (based on date_installed day)
    // We do not send here; only show banners (server-side computation)
    $rows = [];
    $u = $conn->query("SELECT id, full_name, email, date_installed FROM users WHERE email IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    $now = new DateTime('now');
    foreach ($u as $usr) {
      if (!$usr['date_installed']) continue;
      $di = new DateTime($usr['date_installed']);
      $dueDay = (int)$di->format('d');

      // due date = this month's same day; if past, next month
      $due = new DateTime('first day of this month');
      $due->setDate((int)$due->format('Y'), (int)$due->format('m'), min($dueDay, 28)); // simpler bound
      $todayDay=(int)(new DateTime())->format('d');
      if ($todayDay > (int)$due->format('d')) { $due->modify('first day of next month')->setDate((int)$due->format('Y'), (int)$due->format('m'), min($dueDay, 28)); }

      $oneWeek  = (clone $due)->modify('-7 days');
      $threeDay = (clone $due)->modify('-3 days');

      // fetch latest status from reminders table
      $st = $conn->prepare("SELECT reminder_type,status,sent_at FROM reminders WHERE client_id=:cid AND cycle_date=:cycle ORDER BY id DESC");
      $st->execute([':cid'=>$usr['id'], ':cycle'=>$due->format('Y-m-d')]);
      $existing = $st->fetchAll(PDO::FETCH_ASSOC);

      // build banners
      $labels = [];
      $nowDate = new DateTime('today');

      // 1 week banner
      $oneExists = array_filter($existing, fn($x)=>$x['reminder_type']==='1_WEEK');
      $oneSent   = $oneExists && $oneExists[0]['status']==='sent';
      if ($nowDate <= $oneWeek && $nowDate->diff($oneWeek)->days <= 7) {
        $labels[] = ['type'=>'1_WEEK','status'=>$oneSent?'sent':'upcoming','date'=>$oneWeek->format('Y-m-d')];
      } elseif ($nowDate == $oneWeek) {
        $labels[] = ['type'=>'1_WEEK','status'=>$oneSent?'sent':'due today','date'=>$oneWeek->format('Y-m-d')];
      }

      // 3 days banner
      $threeExists = array_filter($existing, fn($x)=>$x['reminder_type']==='3_DAYS');
      $threeSent   = $threeExists && $threeExists[0]['status']==='sent';
      if ($nowDate <= $threeDay && $nowDate->diff($threeDay)->days <= 7) {
        $labels[] = ['type'=>'3_DAYS','status'=>$threeSent?'sent':'upcoming','date'=>$threeDay->format('Y-m-d')];
      } elseif ($nowDate == $threeDay) {
        $labels[] = ['type'=>'3_DAYS','status'=>$threeSent?'sent':'due today','date'=>$threeDay->format('Y-m-d')];
      }

      if (!$labels) continue;

      $name = $usr['full_name'];
      if ($search && stripos($name.$usr['email'],$search)===false) continue;

      $rows[] = [
        'user_id'=>$usr['id'],
        'name'=>$usr['full_name'],
        'email'=>$usr['email'],
        'due'=>$due->format('Y-m-d'),
        'banners'=>$labels
      ];
    }

    echo json_encode($rows); exit;
  }

  // default
  http_response_code(400); echo 'bad'; exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<style>
body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:#f6fff6;overflow:hidden}
header{height:60px;background:#009900;color:#fff;display:flex;align-items:center;padding:0 16px;justify-content:space-between;font-weight:700}
#hamb{cursor:pointer;font-size:26px;background:none;border:none;color:#fff;transition:transform .2s}
#hamb.active{transform:rotate(90deg)}
#overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;z-index:8}
#sidebar{position:fixed;top:0;left:0;width:260px;height:100vh;background:#006b00;color:#fff;transform:translateX(-100%);transition:.25s;z-index:9;box-shadow:5px 0 10px rgba(0,0,0,.2)}
#sidebar.active{transform:translateX(0)}
#sidebar h2{margin:0;padding:18px;background:#005c00;text-align:center}
#sidebar a{display:block;padding:14px 18px;text-decoration:none;color:#fff}
#sidebar a:hover{background:#00aa00}
#tabs{display:flex;gap:8px;padding:10px 14px;background:#eaffea;border-bottom:1px solid #cce5cc}
.tab{padding:8px 14px;border-radius:6px;cursor:pointer;color:#006b00;font-weight:700}
.tab.active{background:#006b00;color:#fff}
#main{display:flex;height:calc(100vh - 100px)}
#client-list{width:320px;overflow-y:auto;background:#fff;border-right:1px solid #ddd;padding:10px}
.client-item{padding:10px;border-radius:8px;background:#fff;box-shadow:0 1px 5px rgba(0,0,0,.08);display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;cursor:pointer}
.client-item:hover{background:#e8ffe8}
.client-email{font-size:12px;color:#006b00}
.assigned{font-size:12px;color:#555}
.actions .assign,.actions .unassign,.actions .lock{border:none;border-radius:16px;padding:6px 10px;color:#fff;cursor:pointer}
.assign{background:#00aa00}.unassign{background:#cc0000}.lock{background:#777;cursor:not-allowed}
#chat-area{flex:1;display:flex;flex-direction:column;background:#fff;position:relative}
#chat-head{background:#009900;color:#fff;padding:10px 14px;font-weight:800}
#messages{flex:1;overflow-y:auto;padding:18px;position:relative}
#messages::before{content:"";position:absolute;top:50%;left:50%;width:420px;height:420px;background:url('<?= $logoPath ?>') no-repeat center;background-size:contain;opacity:.06;transform:translate(-50%,-50%)}
.bubble{max-width:70%;padding:10px 12px;border-radius:12px;margin:6px 0;clear:both;font-size:14px}
.client{background:#e9ffe9;float:left}
.csr{background:#ccf0ff;float:right}
#input{display:flex;gap:8px;border-top:1px solid #ddd;padding:10px;background:#fff}
#input input{flex:1;border:1px solid #ccc;padding:10px;border-radius:8px}
#input button{background:#00aa00;border:none;color:#fff;padding:10px 16px;border-radius:8px;cursor:pointer;font-weight:700}
#reminders{display:none;flex-direction:column;width:100%}
#rem-filter{padding:8px;border-bottom:1px solid #ddd;background:#f8fff8}
.badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;color:#fff;margin-left:6px}
.badge.upcoming{background:#ff9800}.badge["due today"]{background:#e91e63}.badge.sent{background:#2196f3}
.card{background:#fff;margin:10px;border:1px solid #e1e1e1;border-radius:8px;padding:10px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
</style>
</head>
<body>
<div id="overlay" onclick="toggleSidebar()"></div>
<div id="sidebar">
  <h2>Menu</h2>
  <a onclick="activate('all')">💬 Chat Dashboard</a>
  <a onclick="activate('mine')">👤 My Clients</a>
  <a onclick="activate('rem')">⏰ Reminders</a>
  <a href="survey_responses.php">📝 Survey Responses</a>
  <a href="edit_profile.php">👤 Edit Profile</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<header>
  <button id="hamb" onclick="toggleSidebar()">☰</button>
  <div>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></div>
</header>

<div id="tabs">
  <div id="t-all"  class="tab active" onclick="activate('all')">💬 All Clients</div>
  <div id="t-mine" class="tab"         onclick="activate('mine')">👤 My Clients</div>
  <div id="t-rem"  class="tab"         onclick="activate('rem')">⏰ Reminders</div>
  <div class="tab" onclick="location.href='survey_responses.php'">📝 Survey Responses</div>
</div>

<div id="main">
  <!-- LEFT -->
  <div id="client-list"></div>

  <!-- RIGHT -->
  <div id="chat-area">
    <div id="chat-head"><span id="chat-title">Select a client to view messages</span></div>
    <div id="messages"></div>
    <div id="input" style="display:none;">
      <input id="msg" placeholder="Type a reply…">
      <button onclick="sendMsg()">Send</button>
    </div>

    <!-- REMINDERS VIEW -->
    <div id="reminders">
      <div id="rem-filter">
        <input id="rem-q" placeholder="Search by name/email..." onkeyup="loadReminders()" style="padding:8px;border:1px solid #ccc;border-radius:6px;width:260px">
      </div>
      <div id="rem-list"></div>
    </div>
  </div>
</div>

<script>
let currentTab='all', clientId=0;
const hamb=document.getElementById('hamb');

function toggleSidebar(){
  const s=document.getElementById('sidebar');const o=document.getElementById('overlay');
  const open=s.classList.contains('active');
  if(open){s.classList.remove('active');o.style.display='none';hamb.classList.remove('active');}
  else{s.classList.add('active');o.style.display='block';hamb.classList.add('active');}
}

function activate(tab){
  currentTab=tab==='rem'?'all':tab; // load clients for all/mine; reminders is separate pane
  document.getElementById('t-all').classList.toggle('active',tab==='all');
  document.getElementById('t-mine').classList.toggle('active',tab==='mine');
  document.getElementById('t-rem').classList.toggle('active',tab==='rem');

  // show/hide panels
  document.getElementById('reminders').style.display = (tab==='rem') ? 'flex' : 'none';
  document.getElementById('messages').style.display  = (tab==='rem') ? 'none' : 'block';
  document.getElementById('input').style.display     = (tab==='rem') ? 'none' : 'none'; // hidden until a client is selected
  document.getElementById('chat-head').style.display = (tab==='rem') ? 'none' : 'block';

  toggleSidebar();
  if(tab==='rem'){ loadReminders(); } else { loadClients(); }
}

function loadClients(){
  fetch('csr_dashboard.php?ajax=clients&tab='+currentTab)
  .then(r=>r.text()).then(html=>{
    document.getElementById('client-list').innerHTML=html;
    document.querySelectorAll('.client-item').forEach(el=>{
      el.onclick=()=>selectClient(el);
    });
  });
}

function selectClient(el){
  clientId=parseInt(el.dataset.id);
  const name=el.dataset.name;
  document.getElementById('chat-title').textContent='Chat with '+name;
  document.getElementById('messages').style.display='block';
  document.getElementById('input').style.display='flex';
  loadChat();
}

function loadChat(){
  if(!clientId)return;
  fetch('csr_dashboard.php?ajax=load_chat&client_id='+clientId).then(r=>r.json()).then(list=>{
    const box=document.getElementById('messages'); box.innerHTML='';
    list.forEach(m=>{
      const div=document.createElement('div');
      div.className='bubble '+(m.sender_type==='csr'?'csr':'client');
      div.textContent=((m.sender_type==='csr')?(m.csr_fullname||'CSR'):(m.client_name||'Client'))+': '+m.message;
      box.appendChild(div);
    });
    box.scrollTop=box.scrollHeight;
  });
}

function assignClient(id){post('assign',id);}
function unassignClient(id){ if(!confirm('Unassign this client?'))return; post('unassign',id);}
function post(action,id){
  fetch('csr_dashboard.php?ajax='+action,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'client_id='+encodeURIComponent(id)})
    .then(r=>r.text()).then(t=>{ if(t==='ok'){loadClients();} else if(t==='taken'){alert('Already assigned.');loadClients();}});
}

function sendMsg(){
  const input=document.getElementById('msg'); const text=input.value.trim();
  if(!text||!clientId)return;
  const body=new URLSearchParams({sender_type:'csr',message:text,client_id:String(clientId)});
  fetch('../SKYTRUFIBER/save_chat.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
   .then(()=>{input.value='';loadChat();});
}

// Reminders
function loadReminders(){
  const q=document.getElementById('rem-q').value.trim();
  fetch('csr_dashboard.php?ajax=reminders&q='+encodeURIComponent(q))
    .then(r=>r.json()).then(rows=>{
      const box=document.getElementById('rem-list'); box.innerHTML='';
      if(!rows.length){box.innerHTML='<div class="card">No upcoming reminders found.</div>';return;}
      rows.forEach(r=>{
        const container=document.createElement('div'); container.className='card';
        let badges='';
        r.banners.forEach(b=>{
          const label = (b.status==='upcoming')?'Upcoming':(b.status==='due today'?'Due Today':'Sent');
          const cls = (b.status==='sent')?'sent':((b.status==='due today')?'due today':'upcoming');
          badges+=`<span class="badge ${cls}">${b.type.replace('_',' ')} — ${label} (${b.date})</span>`;
        });
        container.innerHTML=`<div><strong>${r.name}</strong> &lt;${r.email}&gt;</div>
          <div>Cycle due: <b>${r.due}</b></div>
          <div style="margin-top:6px">${badges}</div>`;
        box.appendChild(container);
      });
    });
}

// init
window.onload=()=>{activate('all');setInterval(()=>{if(document.getElementById('messages').style.display!=='none'&&clientId)loadChat();if(document.getElementById('reminders').style.display!=='none')loadReminders();},4000);};
</script>
</body>
</html>
