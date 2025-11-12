<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// --- AJAX HANDLERS ---
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    // Load clients
    if ($_GET['ajax'] === 'clients') {
        $tab = $_GET['tab'] ?? 'all';
        if ($tab === 'mine') {
            $stmt = $conn->prepare("SELECT id, name AS full_name, assigned_csr FROM clients WHERE assigned_csr = :c ORDER BY name ASC");
            $stmt->execute([':c'=>$csr_user]);
        } else {
            $stmt = $conn->query("SELECT id, name AS full_name, assigned_csr FROM clients ORDER BY name ASC");
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Load chat
    if ($_GET['ajax'] === 'chat' && isset($_GET['id'])) {
        $cid = (int)$_GET['id'];
        $stmt = $conn->prepare("SELECT message, sender_type, csr_fullname, created_at FROM chat WHERE client_id = :id ORDER BY created_at ASC");
        $stmt->execute([':id'=>$cid]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // Send message
    if ($_GET['ajax'] === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $cid = (int)$_POST['client_id'];
        $msg = trim($_POST['msg']);
        if ($cid && $msg) {
            $q = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at) VALUES (:cid, 'csr', :m, :csr, NOW())");
            $q->execute([':cid'=>$cid, ':m'=>$msg, ':csr'=>$csr_user]);
            echo json_encode(['ok'=>1]);
        } else echo json_encode(['ok'=>0]);
        exit;
    }
    exit;
}

// --- MAIN PAGE ---
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u'=>$csr_user]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $r['full_name'] ?? $csr_user;
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="csr_dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
<header>
  <div class="header-left">
    <img src="<?= $logoPath ?>" alt="Logo">
    <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
  </div>
  <div class="header-right">
    <a href="csr_logout.php">Logout</a>
  </div>
</header>

<div class="tabs">
  <button id="tabAll" class="active" onclick="loadClients('all')">💬 All Clients</button>
  <button id="tabMine" onclick="loadClients('mine')">👤 My Clients</button>
  <button onclick="window.location='survey_responses.php'">📝 Survey Responses</button>
  <button onclick="window.location='update_profile.php'">👤 Edit Profile</button>
</div>

<div id="layout">
  <div id="clientList"></div>
  <div id="chat">
    <div id="messages"></div>
    <div id="composer" style="display:none;">
      <input id="msgInput" placeholder="Type a reply...">
      <button onclick="sendMsg()">Send</button>
    </div>
  </div>
</div>

<script>
let currentClient = null;

function loadClients(tab='all'){
  document.querySelectorAll('.tabs button').forEach(b=>b.classList.remove('active'));
  document.getElementById(tab==='mine'?'tabMine':'tabAll').classList.add('active');
  fetch(`?ajax=clients&tab=${tab}`).then(r=>r.json()).then(d=>{
    const box=document.getElementById('clientList');
    if(!d.length){box.innerHTML='<div class="empty">No clients found</div>';return;}
    box.innerHTML='';
    d.forEach(c=>{
      const div=document.createElement('div');
      div.className='client';
      div.innerHTML=`<div class='client-name'>${c.full_name}</div>
                     <div class='assign'>Assigned: ${c.assigned_csr||'Unassigned'}</div>`;
      div.onclick=()=>selectClient(c.id,c.full_name);
      box.appendChild(div);
    });
  });
}

function selectClient(id,name){
  currentClient=id;
  document.getElementById('composer').style.display='flex';
  loadChat();
}

function loadChat(){
  if(!currentClient)return;
  fetch(`?ajax=chat&id=${currentClient}`).then(r=>r.json()).then(msgs=>{
    const box=document.getElementById('messages');
    box.innerHTML='';
    msgs.forEach(m=>{
      const div=document.createElement('div');
      div.className='msg '+(m.sender_type==='csr'?'csrmsg':'clientmsg');
      div.textContent=m.message;
      box.appendChild(div);
    });
    box.scrollTop=box.scrollHeight;
  });
}

function sendMsg(){
  const val=document.getElementById('msgInput').value.trim();
  if(!val||!currentClient)return;
  const fd=new FormData();
  fd.append('client_id',currentClient);
  fd.append('msg',val);
  fetch('?ajax=send',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
    if(res.ok){document.getElementById('msgInput').value='';loadChat();}
  });
}

loadClients();
</script>
</body>
</html>
