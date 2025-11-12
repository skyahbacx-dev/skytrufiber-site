<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// ========== AJAX HANDLERS ==========
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    switch ($_GET['ajax']) {
        case 'clients':
            $tab = $_GET['tab'] ?? 'all';
            if ($tab === 'mine') {
                $stmt = $conn->prepare("SELECT id, name AS full_name, assigned_csr, is_online FROM clients WHERE assigned_csr = :c ORDER BY name ASC");
                $stmt->execute([':c' => $csr_user]);
            } else {
                $stmt = $conn->query("SELECT id, name AS full_name, assigned_csr, is_online FROM clients ORDER BY name ASC");
            }
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;

        case 'chat':
            $cid = (int)$_GET['id'];
            $stmt = $conn->prepare("
                SELECT message, sender_type, csr_fullname, created_at,
                (SELECT name FROM clients WHERE id = chat.client_id LIMIT 1) AS client_name
                FROM chat WHERE client_id = :id ORDER BY created_at ASC
            ");
            $stmt->execute([':id' => $cid]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            exit;

        case 'send':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $cid = (int)$_POST['client_id'];
                $msg = trim($_POST['msg']);
                if ($cid && $msg) {
                    $q = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at) VALUES (:cid, 'csr', :m, :csr, NOW())");
                    $q->execute([':cid' => $cid, ':m' => $msg, ':csr' => $csr_user]);
                    echo json_encode(['ok' => 1]);
                } else echo json_encode(['ok' => 0]);
            }
            exit;

        case 'assign':
            $cid = (int)$_POST['client_id'];
            $assign = $_POST['assign'] ?? 'none';
            if ($assign === 'assign') {
                $stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :cid");
                $stmt->execute([':csr' => $csr_user, ':cid' => $cid]);
            } else {
                $stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :cid");
                $stmt->execute([':cid' => $cid]);
            }
            echo json_encode(['ok' => 1]);
            exit;
    }
    exit;
}

// ========== MAIN DASHBOARD ==========
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
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

<div class="sidebar">
  <button class="tab active" data-tab="all">💬 All Clients</button>
  <button class="tab" data-tab="mine">👤 My Clients</button>
  <button onclick="window.location='survey_responses.php'">📝 Survey Responses</button>
  <button onclick="window.location='update_profile.php'">👤 Update Profile</button>
</div>

<div id="main">
  <div id="clientList"></div>
  <div id="chat">
    <div id="chatHeader">
      <div>
        <span id="clientName">Select a client</span><br>
        <small id="csrName">You are <?= htmlspecialchars($csr_fullname) ?></small>
      </div>
      <div id="assignBtn"></div>
    </div>

    <div id="messages"></div>

    <div id="composer" style="display:none;">
      <input id="msgInput" placeholder="Type a reply...">
      <button onclick="sendMsg()">Send</button>
    </div>
  </div>
</div>

<script>
const CSR_NAME = <?= json_encode($csr_fullname) ?>;
let currentClient = null;
let currentClientName = '';
let chatRefreshInterval = null;

// Sidebar tab switching
document.querySelectorAll('.sidebar .tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.sidebar .tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    loadClients(btn.dataset.tab);
  });
});

// Load client list
function loadClients(tab='all') {
  fetch(`?ajax=clients&tab=${tab}`)
    .then(r => r.json())
    .then(d => {
      const box = document.getElementById('clientList');
      if (!d.length) {
        box.innerHTML = '<div class="empty">No clients found</div>';
        return;
      }
      box.innerHTML = '';
      d.forEach(c => {
        const onlineDot = c.is_online == 1 ? '<span class="dot online"></span>' : '<span class="dot offline"></span>';
        const div = document.createElement('div');
        div.className = 'client';
        div.innerHTML = `
          <div class="client-row">
            ${onlineDot}
            <div class="client-info">
              <div><strong>${c.full_name}</strong></div>
              <div class='assign'>Assigned: ${c.assigned_csr || 'Unassigned'}</div>
            </div>
          </div>`;
        div.onclick = () => selectClient(c.id, c.full_name, c.assigned_csr);
        box.appendChild(div);
      });
    });
}

// Select client and show chat
function selectClient(id, name, assigned) {
  currentClient = id;
  currentClientName = name;
  document.getElementById('clientName').textContent = `Chatting with ${name}`;
  document.getElementById('composer').style.display = 'flex';
  document.getElementById('assignBtn').innerHTML =
    assigned === null
      ? `<button onclick="assignClient('assign')">Assign to me</button>`
      : `<button onclick="assignClient('unassign')">Unassign</button>`;

  if (chatRefreshInterval) clearInterval(chatRefreshInterval);
  loadChat();
  chatRefreshInterval = setInterval(loadChat, 5000);
}

// Load chat messages
function loadChat() {
  if (!currentClient) return;
  fetch(`?ajax=chat&id=${currentClient}`)
    .then(r => r.json())
    .then(msgs => {
      const box = document.getElementById('messages');
      box.innerHTML = '';
      msgs.forEach(m => {
        const div = document.createElement('div');
        div.className = 'msg ' + (m.sender_type === 'csr' ? 'csrmsg' : 'clientmsg');
        const senderName = (m.sender_type === 'csr') ? m.csr_fullname : m.client_name;
        div.innerHTML = `<strong>${senderName}:</strong> ${m.message}`;
        box.appendChild(div);
      });
      box.scrollTop = box.scrollHeight;
    });
}

// Send message
function sendMsg() {
  const val = document.getElementById('msgInput').value.trim();
  if (!val || !currentClient) return;
  const fd = new FormData();
  fd.append('client_id', currentClient);
  fd.append('msg', val);
  fetch('?ajax=send', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if (res.ok) {
        document.getElementById('msgInput').value = '';
        loadChat();
      }
    });
}

// Assign or unassign client
function assignClient(action) {
  if (!currentClient) return;
  const fd = new FormData();
  fd.append('client_id', currentClient);
  fd.append('assign', action);
  fetch('?ajax=assign', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(() => {
      loadClients(document.querySelector('.sidebar .tab.active').dataset.tab);
      loadChat();
    });
}

loadClients();
</script>

</body>
</html>
