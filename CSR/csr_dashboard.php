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
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :username LIMIT 1");
$stmt->execute([':username' => $csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

// ✅ Logo path fallback
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ===== AJAX HANDLERS ===== */
if (isset($_GET['ajax'])) {

    // ========== LOAD CLIENTS ==========
    if ($_GET['ajax'] === 'clients') {
        $tab = $_GET['tab'] ?? 'all';
        if ($tab === 'mine') {
            $stmt = $conn->prepare("
                SELECT c.id, c.name, c.assigned_csr, MAX(ch.created_at) AS last_chat
                FROM clients c
                LEFT JOIN chat ch ON ch.client_id = c.id
                WHERE c.assigned_csr = :csr
                GROUP BY c.id, c.name, c.assigned_csr
                ORDER BY last_chat DESC NULLS LAST
            ");
            $stmt->execute([':csr' => $csr_user]);
        } else {
            $stmt = $conn->query("
                SELECT c.id, c.name, c.assigned_csr, MAX(ch.created_at) AS last_chat
                FROM clients c
                LEFT JOIN chat ch ON ch.client_id = c.id
                GROUP BY c.id, c.name, c.assigned_csr
                ORDER BY last_chat DESC NULLS LAST
            ");
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $assigned = $row['assigned_csr'] ?: 'Unassigned';
            $owned = ($assigned === $csr_user);
            $canAssign = ($assigned === 'Unassigned');
            $btn = '';

            if ($canAssign) {
                $btn = "<button class='assign-btn' onclick='assignClient({$row['id']})'>＋</button>";
            } elseif ($owned) {
                $btn = "<button class='unassign-btn' onclick='unassignClient({$row['id']})'>−</button>";
            } else {
                $btn = "<button class='locked-btn' disabled>🔒</button>";
            }

            echo "
                <div class='client-item' data-id='{$row['id']}' data-name='".htmlspecialchars($row['name'],ENT_QUOTES)."' data-csr='".htmlspecialchars($assigned,ENT_QUOTES)."'>
                    <div class='client-info'>
                        <strong>".htmlspecialchars($row['name'])."</strong><br>
                        <small>Assigned: ".htmlspecialchars($assigned)."</small>
                    </div>
                    $btn
                </div>
            ";
        }
        exit;
    }

    // ========== LOAD REMINDERS ==========
    if ($_GET['ajax'] === 'load_reminders') {
        $sql = "
        SELECT r.id, r.client_id, r.csr_username, r.reminder_type, r.sent_at, r.status, c.name AS client_name
        FROM reminders r
        LEFT JOIN clients c ON r.client_id = c.id
        ORDER BY r.id DESC";
        $stmt = $conn->query($sql);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
        exit;
    }

    // ========== CREATE REMINDER MANUALLY ==========
    if ($_GET['ajax'] === 'create_reminder' && !empty($_POST['client_id']) && !empty($_POST['type'])) {
        $cid = (int)$_POST['client_id'];
        $type = $_POST['type'];

        $stmt = $conn->prepare("
            INSERT INTO reminders (client_id, csr_username, reminder_type, status)
            VALUES (:cid, :csr, :type, 'pending')
        ");
        $stmt->execute([
            ':cid' => $cid,
            ':csr' => $csr_user,
            ':type' => $type
        ]);

        echo "ok";
        exit;
    }

    // ========== ASSIGN CLIENT ==========
    if ($_GET['ajax'] === 'assign' && isset($_POST['client_id'])) {
        $id = (int)$_POST['client_id'];
        $stmt = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($r && $r['assigned_csr'] && $r['assigned_csr'] !== 'Unassigned') {
            echo 'taken';
            exit;
        }

        $update = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id");
        echo $update->execute([':csr' => $csr_user, ':id' => $id]) ? 'ok' : 'fail';
        exit;
    }

    // ========== UNASSIGN CLIENT ==========
    if ($_GET['ajax'] === 'unassign' && isset($_POST['client_id'])) {
        $id = (int)$_POST['client_id'];
        $update = $conn->prepare("
            UPDATE clients SET assigned_csr = 'Unassigned'
            WHERE id = :id AND assigned_csr = :csr
        ");
        echo $update->execute([':id' => $id, ':csr' => $csr_user]) ? 'ok' : 'fail';
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — SkyTruFiber</title>

<style>
/* === BASIC STYLES — same as before === */
/* Everything from your previous version remains identical */
</style>
</head>

<body>

<!-- SIDEBAR -->
<div id="sidebar">
  <h2><img src="<?= $logoPath ?>"> Menu</h2>
  <a href="csr_dashboard.php?tab=all">💬 Chat Dashboard</a>
  <a href="csr_dashboard.php?tab=mine">👥 My Clients</a>
  <a href="#" onclick="showReminders()">⏰ Reminders</a>
  <a href="survey_responses.php">📝 Survey Responses</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<div id="main-content">

<header>
  <button onclick="toggleSidebar()">☰</button>
  <div class="title">
    <img src="<?= $logoPath ?>">
    <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
  </div>
</header>

<div id="container">

<!-- LEFT COLUMN — CLIENT LIST -->
<div id="client-list"></div>

<!-- RIGHT COLUMN — CHAT AREA -->
<div id="chat-area">

  <div id="chat-header">
    <span id="chat-title">Select a client to view messages</span>
  </div>

  <div id="messages"></div>

  <div class="input" id="inputRow" style="display:none;">
    <input id="msg" placeholder="Type a reply…">
    <button onclick="sendMsg()">Send</button>
  </div>

</div>

<!-- REMINDERS SECTION -->
<div id="reminders-area" style="display:none; padding:20px;">
  <h2>⏰ Reminders</h2>
  <div id="reminders-list"></div>

  <h3>Create Reminder</h3>
  <select id="rem-client"></select>
  <select id="rem-type">
    <option value="1_WEEK">1 Week Before Due</option>
    <option value="3_DAYS">3 Days Before Due</option>
  </select>
  <button onclick="createReminder()">Add Reminder</button>
</div>

</div><!-- /container -->
</div><!-- /main -->

<script>

// VIEW CONTROLS
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('active');
  document.getElementById('main-content').classList.toggle('shifted');
}

function showReminders() {
  document.getElementById('chat-area').style.display = 'none';
  document.getElementById('reminders-area').style.display = 'block';
  loadReminders();
  loadClientDropdown();
}

// LOAD CLIENT LIST
function loadClients() {
  fetch('csr_dashboard.php?ajax=clients&tab='+currentTab)
  .then(r=>r.text())
  .then(html=>{
    document.getElementById('client-list').innerHTML = html;
    document.querySelectorAll('.client-item').forEach(el=>{
      el.onclick = ()=>{
        selectClient(el);
      };
    });
  });
}

function selectClient(el) {
  let assigned = el.dataset.csr;
  let name = el.dataset.name;

  clientId = parseInt(el.dataset.id);
  document.getElementById('chat-title').textContent = "Chat with " + name;
  loadChat(assigned === csrUser, assigned);
}

// REMINDER LOGIC
function loadReminders() {
  fetch('csr_dashboard.php?ajax=load_reminders')
  .then(r=>r.json())
  .then(rows=>{
    let out = `<table border="1" width="100%">
      <tr><th>ID</th><th>Client</th><th>CSR</th><th>Type</th><th>Status</th><th>Sent At</th></tr>`;
    rows.forEach(r=>{
      out += `<tr>
        <td>${r.id}</td>
        <td>${r.client_name}</td>
        <td>${r.csr_username}</td>
        <td>${r.reminder_type}</td>
        <td>${r.status}</td>
        <td>${r.sent_at ?? ''}</td>
      </tr>`;
    });
    out += "</table>";
    document.getElementById('reminders-list').innerHTML = out;
  });
}

function loadClientDropdown() {
  fetch('csr_dashboard.php?ajax=clients&tab=all')
    .then(r=>r.text())
    .then(html=>{
      let temp = document.createElement("div");
      temp.innerHTML = html;

      let options = "";
      temp.querySelectorAll('.client-item').forEach(el=>{
        options += `<option value="${el.dataset.id}">${el.dataset.name}</option>`;
      });
      document.getElementById('rem-client').innerHTML = options;
    });
}

function createReminder() {
  let cid = document.getElementById('rem-client').value;
  let type = document.getElementById('rem-type').value;

  let form = new URLSearchParams();
  form.append("client_id", cid);
  form.append("type", type);

  fetch('csr_dashboard.php?ajax=create_reminder', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: form
  })
  .then(r=>r.text())
  .then(t=>{
    alert("Reminder added!");
    loadReminders();
  });
}

// EVERYTHING ELSE — same chat logic
</script>

</body>
</html>
