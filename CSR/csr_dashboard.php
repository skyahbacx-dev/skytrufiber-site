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

/* ===== RESET ===== */

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Segoe UI", Arial, sans-serif;
}

body {
  height: 100vh;
  display: flex;
  overflow: hidden;
  background: #f4fff4;
  position: relative;
}

/* ===== BACKGROUND LOGO WATERMARK ===== */

body::before {
  content: "";
  position: absolute;
  inset: 0;
  background: url('<?= $logoPath ?>') no-repeat center;
  background-size: 600px;
  opacity: 0.04;
  z-index: 0;
  pointer-events: none;
}

/* ===== SIDEBAR ===== */

#sidebar {
  width: 250px;
  background: #038003;
  color: white;
  position: fixed;
  top: 0;
  bottom: 0;
  left: -250px;
  transition: .3s ease;
  z-index: 10;
  display: flex;
  flex-direction: column;
}

#sidebar.active {
  left: 0;
}

#sidebar h2 {
  padding: 18px;
  background: #026602;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 19px;
  font-weight: bold;
}

#sidebar h2 img {
  height: 30px;
}

#sidebar a {
  padding: 16px 20px;
  color: white;
  text-decoration: none;
  font-weight: 600;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  transition: .2s;
}

#sidebar a:hover {
  background: #00a000;
}

/* ===== MAIN CONTENT ===== */

#main-content {
  width: 100%;
  margin-left: 0;
  transition: .3s ease;
  display: flex;
  flex-direction: column;
  z-index: 1;
}

#main-content.shifted {
  margin-left: 250px;
}

header {
  height: 60px;
  background: #02a402;
  color: white;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  font-size: 18px;
  font-weight: bold;
}

header button {
  background: none;
  border: none;
  font-size: 26px;
  color: white;
  cursor: pointer;
}

/* ===== CONTAINER LAYOUT ===== */

#container {
  display: flex;
  width: 100%;
  height: calc(100vh - 60px);
}

/* ===== CLIENT LIST ===== */

#client-list {
  width: 300px;
  background: white;
  overflow-y: auto;
  border-right: 1px solid #ddd;
  padding: 10px;
}

.client-item {
  background: #ffffff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  margin-bottom: 8px;
  border-radius: 10px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
  cursor: pointer;
  transition: .2s;
}

.client-item:hover {
  background: #e0ffe0;
}

.client-item.active {
  background: #c6f5c6;
  border: 2px solid #02a402;
}

/* ===== BUTTONS ===== */

.assign-btn,
.unassign-btn,
.locked-btn {
  height: 34px;
  width: 34px;
  font-size: 18px;
  border-radius: 50%;
  border: none;
  color: white;
  cursor: pointer;
}

.assign-btn {
  background: #02a402;
}

.unassign-btn {
  background: #cc0000;
}

.locked-btn {
  background: #777;
}

/* ===== CHAT AREA ===== */

#chat-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: white;
  position: relative;
}

#chat-header {
  background: #02a402;
  color: white;
  padding: 12px;
  font-weight: bold;
  font-size: 17px;
}

#messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  position: relative;
}

.month-label {
  background: #d8ffd8;
  padding: 6px;
  text-align: center;
  margin: 12px 0;
  font-size: 12px;
  font-weight: bold;
  border-radius: 8px;
  color: #046c04;
}

.bubble {
  max-width: 70%;
  padding: 12px 14px;
  margin-bottom: 10px;
  border-radius: 12px;
  font-size: 14px;
  line-height: 1.5;
  clear: both;
}

.client {
  background: #eaffea;
  float: left;
}

.csr {
  background: #dbf0ff;
  float: right;
}

.timestamp {
  display: block;
  font-size: 11px;
  color: #666;
  margin-top: 5px;
  text-align: right;
}

/* ===== CHAT INPUT ===== */

.input {
  border-top: 1px solid #ccc;
  padding: 12px;
  display: flex;
  gap: 10px;
}

.input input {
  flex: 1;
  padding: 10px;
  border-radius: 8px;
  outline: none;
  border: 1px solid #ccc;
}

.input button {
  background: #02a402;
  padding: 10px 16px;
  border: none;
  color: white;
  cursor: pointer;
  border-radius: 8px;
  font-weight: bold;
}

/* ===== REMINDERS SECTION ===== */

#reminders-area {
  width: 100%;
  background: white;
  overflow-y: auto;
}

#reminders-area h2 {
  font-size: 22px;
  margin-bottom: 20px;
}

#reminders-list table {
  width: 100%;
  border-collapse: collapse;
}

#reminders-list th,
#reminders-list td {
  border: 1px solid #ddd;
  padding: 8px;
  font-size: 14px;
}

#reminders-list th {
  background: #02a402;
  color: white;
}

#reminders-list tr:nth-child(even) {
  background: #f0fff0;
}

#rem-type,
#rem-client,
#rem-create-btn {
  margin-top: 10px;
}

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
