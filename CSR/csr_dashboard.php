<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];
// Display name for CSR (pull full name if available)
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u'=>$csr_user]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $r['full_name'] ?? $csr_user;

// logo fallback
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/*
=====================================
AJAX ROUTES (when ?ajax=...)
=====================================
*/
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['ajax'];

    // LOAD CLIENTS (all or mine)
    if ($action === 'load_clients') {
        $type = $_GET['type'] ?? 'all';
        if ($type === 'mine') {
            $stmt = $conn->prepare(
                "SELECT id, COALESCE(full_name, name, client_name) AS name, last_active, assigned_csr 
                 FROM clients WHERE assigned_csr = :csr ORDER BY COALESCE(last_active, created_at) DESC"
            );
            $stmt->execute([':csr' => $csr_user]);
        } else {
            $stmt = $conn->query(
                "SELECT id, COALESCE(full_name, name, client_name) AS name, last_active, assigned_csr 
                 FROM clients ORDER BY COALESCE(last_active, created_at) DESC"
            );
        }
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // compute online
        foreach ($list as &$c) {
            $last = strtotime($c['last_active'] ?: '1970-01-01');
            $c['status'] = (time() - $last < 300) ? 'Online' : 'Offline';
        }
        echo json_encode($list);
        exit;
    }

    // LOAD CHAT for a client
    if ($action === 'load_chat' && isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];
        $stmt = $conn->prepare("SELECT message, sender_type, COALESCE(sender_name, csr_fullname, '') AS sender_name, created_at FROM chat WHERE client_id = :cid ORDER BY created_at ASC");
        $stmt->execute([':cid' => $cid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
        exit;
    }

    // SEND MESSAGE
    if ($action === 'send_msg' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $cid = (int)($_POST['client_id'] ?? 0);
        $msg = trim($_POST['message'] ?? '');
        if ($cid && $msg !== '') {
            $stmt = $conn->prepare("INSERT INTO chat (client_id, message, sender_type, sender_name, created_at) VALUES (:cid, :msg, 'csr', :sname, NOW())");
            $stmt->execute([':cid'=>$cid, ':msg'=>$msg, ':sname'=>$csr_fullname]);
            echo json_encode(['ok'=>true]);
            exit;
        }
        echo json_encode(['ok'=>false]);
        exit;
    }

    // TYPING (simple POST to notify)
    if ($action === 'typing' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $cid = (int)($_POST['client_id'] ?? 0);
        if ($cid) {
            // optional: update typing flag column if you have one
            // $stmt = $conn->prepare("UPDATE clients SET typing_csr = :csr WHERE id = :id");
            // $stmt->execute([':csr'=>$csr_user, ':id'=>$cid]);
            echo json_encode(['ok'=>true]);
            exit;
        }
        echo json_encode(['ok'=>false]);
        exit;
    }

    echo json_encode(['error'=>'invalid action']);
    exit;
}

/*
=====================================
PAGE OUTPUT (non-AJAX)
=====================================
*/
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=2">
</head>
<body>

<header class="topbar">
  <div class="left">
    <button id="hamb" aria-label="Toggle sidebar">☰</button>
    <img src="<?= htmlspecialchars($logoPath) ?>" alt="logo" class="logo">
    <h1>CSR Dashboard — <span id="csrName"><?= htmlspecialchars($csr_fullname) ?></span></h1>
  </div>
  <div class="right">
    <a href="csr_logout.php" class="logout">Logout</a>
  </div>
</header>

<div class="tabs-row">
  <nav class="tabs">
    <button id="tabAll" class="tab active" data-type="all">💬 All Clients</button>
    <button id="tabMine" class="tab" data-type="mine">👤 My Clients</button>
    <a class="tab" href="survey_responses.php">📝 Survey Responses</a>
    <a class="tab" href="update_profile.php">👤 Update Profile</a>
  </nav>
</div>

<div id="wrap" class="wrap">
  <aside id="sidebar" class="sidebar">
    <div class="sidebar-inner">
      <h3>Clients</h3>
      <div id="clientList" class="client-list">
        <!-- filled by JS -->
      </div>
    </div>
  </aside>

  <main class="main-area">
    <section class="chat-header">
      <div class="chat-header-left">
        <img id="clientAvatar" class="avatar" src="CSR/lion.PNG" alt="client avatar">
        <div>
          <div id="clientName" class="client-name">Select a client</div>
          <div id="clientStatus" class="client-status">Offline</div>
        </div>
      </div>
      <div class="chat-header-right">
        <button id="collapseBtn" title="Collapse chat">●</button>
      </div>
    </section>

    <section id="messages" class="messages">
      <p class="placeholder">Select a client to start chatting.</p>
    </section>

    <div id="typingIndicator" class="typing" style="display:none;">
      <span></span><span></span><span></span>
    </div>

    <div class="input-area">
      <input id="msg" type="text" placeholder="Type your message..." autocomplete="off">
      <button id="sendBtn">Send</button>
    </div>
  </main>
</div>

<script>
/* ---------- Globals ---------- */
let currentClientId = null;
let refreshTimer = null;

/* ---------- Sidebar toggle ---------- */
const hamb = document.getElementById('hamb');
const wrap = document.getElementById('wrap');
hamb.addEventListener('click', ()=> {
  document.body.classList.toggle('sidebar-collapsed');
});

/* ---------- Tabs ---------- */
document.querySelectorAll('.tab[data-type]').forEach(btn=>{
  btn.addEventListener('click', e=>{
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    btn.classList.add('active');
    const type = btn.dataset.type;
    loadClients(type);
  });
});

/* ---------- Load clients via AJAX ---------- */
function loadClients(type='all') {
  fetch(`csr_dashboard.php?ajax=load_clients&type=${encodeURIComponent(type)}`)
    .then(r=>r.json())
    .then(list=>{
      const el = document.getElementById('clientList');
      el.innerHTML = '';
      if (!Array.isArray(list) || list.length === 0) {
        el.innerHTML = '<div class="no-clients">No clients found.</div>';
        return;
      }
      list.forEach(c=>{
        const div = document.createElement('div');
        div.className = 'client-item';
        // choose avatar by first letter simple rule (you said lion/penguin)
        const avatar = (c.name && c.name.trim()[0].toUpperCase() <= 'M') ? 'CSR/lion.PNG' : 'CSR/penguin.PNG';
        div.innerHTML = `
          <img src="${avatar}" class="client-avatar" alt="">
          <div class="client-meta">
            <div class="client-title">${escapeHtml(c.name)}</div>
            <div class="client-sub">${escapeHtml(c.status)} ${c.assigned_csr ? '• ' + escapeHtml(c.assigned_csr) : ''}</div>
          </div>
        `;
        div.addEventListener('click', ()=> selectClient(c.id, c.name, c.status));
        el.appendChild(div);
      });
    })
    .catch(err=>console.error(err));
}

/* ---------- Select a client ---------- */
function selectClient(id, name, status) {
  currentClientId = id;
  document.getElementById('clientName').textContent = name;
  document.getElementById('clientStatus').textContent = status;
  document.getElementById('clientAvatar').src = (name.trim()[0].toUpperCase() <= 'M') ? 'CSR/lion.PNG' : 'CSR/penguin.PNG';
  loadChat();
  // start background refresh
  if (refreshTimer) clearInterval(refreshTimer);
  refreshTimer = setInterval(loadChat, 3000);
}

/* ---------- Load chat messages ---------- */
function loadChat() {
  if (!currentClientId) return;
  fetch(`csr_dashboard.php?ajax=load_chat&client_id=${currentClientId}`)
    .then(r=>r.json())
    .then(rows=>{
      const wrap = document.getElementById('messages');
      wrap.innerHTML = '';
      if (!Array.isArray(rows) || rows.length === 0) {
        wrap.innerHTML = '<p class="placeholder">No messages yet. Say hi!</p>';
        return;
      }
      rows.forEach(m=>{
        const mdiv = document.createElement('div');
        const cls = (m.sender_type === 'csr') ? 'message csr' : 'message client';
        mdiv.className = cls;
        // show "Name: message" inside bubble
        const created = new Date(m.created_at).toLocaleString();
        mdiv.innerHTML = `<div class="bubble"><strong>${escapeHtml(m.sender_name)}:</strong> ${escapeHtml(m.message)}<div class="meta">${escapeHtml(created)}</div></div>`;
        wrap.appendChild(mdiv);
      });
      wrap.scrollTop = wrap.scrollHeight;
    })
    .catch(err=>console.error(err));
}

/* ---------- Send message ---------- */
document.getElementById('sendBtn').addEventListener('click', sendMsg);
document.getElementById('msg').addEventListener('keydown', function(e){
  if (e.key === 'Enter') sendMsg();
});

function sendMsg() {
  const input = document.getElementById('msg');
  const message = input.value.trim();
  if (!message || !currentClientId) return;
  const data = new URLSearchParams();
  data.append('client_id', currentClientId);
  data.append('message', message);
  fetch('csr_dashboard.php?ajax=send_msg', { method:'POST', body: data })
    .then(r=>r.json())
    .then(resp=>{
      if (resp.ok) {
        input.value = '';
        loadChat();
      }
    }).catch(err=>console.error(err));
}

/* ---------- Typing indicator (simple animate) ---------- */
let typingTimer;
document.getElementById('msg').addEventListener('input', function() {
  if (!currentClientId) return;
  // show local typing dots
  const typ = document.getElementById('typingIndicator');
  typ.style.display = 'flex';
  clearTimeout(typingTimer);
  typingTimer = setTimeout(()=> typ.style.display = 'none', 1400);
  // notify server (not used heavily in this version)
  fetch('csr_dashboard.php?ajax=typing', { method:'POST', body: new URLSearchParams({ client_id: currentClientId })});
});

/* ---------- small util ---------- */
function escapeHtml(s) {
  if (!s && s !== 0) return '';
  return String(s).replace(/[&<>"'`=\/]/g, function (ch) {
    return ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'
    })[ch];
  });
}

/* ---------- start ---------- */
document.addEventListener('DOMContentLoaded', function(){
  loadClients('all');
});
</script>

</body>
</html>
