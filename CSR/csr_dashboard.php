<?php
session_start();
include '../db_connect.php';

// Ensure CSR is logged in
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Fetch CSR info
$stmt = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$csrRow = $stmt->fetch(PDO::FETCH_ASSOC);

$csr_fullname = $csrRow['full_name'] ?? $csr_user;
$csr_email = $csrRow['email'] ?? "";

// Logo path
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ==========================================================
   AJAX HANDLERS
========================================================== */
if (isset($_GET['ajax'])) {

    /* ---------- Load Client List ---------- */
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
            $assigned = htmlspecialchars($row['assigned_csr'] ?: 'Unassigned');
            $name = htmlspecialchars($row['name']);
            $id = (int)$row['id'];

            $owned = ($assigned === $csr_user);
            $isFree = ($assigned === "Unassigned");

            if ($isFree) {
                $btn = "<button class='assign-btn' onclick='assignClient($id)'>＋</button>";
            } elseif ($owned) {
                $btn = "<button class='unassign-btn' onclick='unassignClient($id)'>−</button>";
            } else {
                $btn = "<button class='locked-btn' disabled>🔒</button>";
            }

            echo "
            <div class='client-item' data-id='$id' data-name='$name' data-csr='$assigned'>
                <div class='client-info'>
                    <strong>$name</strong><br>
                    <small>Assigned: $assigned</small>
                </div>
                $btn
            </div>";
        }
        exit;
    }

    /* ---------- Load Reminders ---------- */
    if ($_GET['ajax'] === 'load_reminders') {
        $search = "%".($_GET['search'] ?? "")."%";

        $stmt = $conn->prepare("
            SELECT r.id, r.client_id, r.csr_username, r.reminder_type, r.status, r.sent_at,
                   c.name AS client_name
            FROM reminders r
            LEFT JOIN clients c ON c.id = r.client_id
            WHERE c.name ILIKE :s OR r.reminder_type ILIKE :s OR r.status ILIKE :s
            ORDER BY r.id DESC
        ");
        $stmt->execute([":s" => $search]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /* ---------- Create Reminder ---------- */
    if ($_GET['ajax'] === 'create_reminder') {
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

    /* ---------- Assign Client ---------- */
    if ($_GET['ajax'] === 'assign') {
        $id = (int)$_POST['client_id'];

        $stmt = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($r && $r['assigned_csr'] !== "Unassigned") {
            echo "taken";
            exit;
        }

        $upd = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id");
        echo $upd->execute([':csr' => $csr_user, ':id' => $id]) ? "ok" : "fail";
        exit;
    }

    /* ---------- Unassign Client ---------- */
    if ($_GET['ajax'] === 'unassign') {
        $id = (int)$_POST['client_id'];

        $upd = $conn->prepare("
            UPDATE clients
            SET assigned_csr = 'Unassigned'
            WHERE id = :id AND assigned_csr = :csr
        ");
        echo $upd->execute([':id' => $id, ':csr' => $csr_user]) ? "ok" : "fail";
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
:root {
  --green: #009900;
  --light-green: #eaffea;
  --dark-green: #006600;
  --white: #ffffff;
}

/* ========== GENERAL LAYOUT ========== */
body {
  margin: 0;
  font-family: "Segoe UI", Arial, sans-serif;
  background: #f5fff5;
  height: 100vh;
  overflow: hidden;
  display: flex;
}

/* ========== SIDEBAR ========== */
#sidebar {
  width: 260px;
  background: var(--green);
  color: white;
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  padding-top: 20px;
  overflow-y: auto;
  z-index: 1000;
  transition: transform .3s ease;
}

#sidebar.closed {
  transform: translateX(-260px);
}

#sidebar h2 {
  text-align: center;
  margin: 0 0 20px;
}
#sidebar h2 img {
  height: 40px;
}

#sidebar a {
  display: block;
  padding: 15px 20px;
  font-size: 16px;
  text-decoration: none;
  color: white;
  font-weight: bold;
}
#sidebar a:hover {
  background: #00b300;
}

/* ========== MAIN ========== */
.main {
  margin-left: 260px;
  transition: margin-left .3s ease;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.main.shifted {
  margin-left: 0;
}

/* ========== HEADER ========== */
.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--green);
  color: white;
  padding: 12px 20px;
}

.header img {
  height: 45px;
}

.header .title {
  display: flex;
  align-items: center;
  gap: 15px;
}

/* ========== HAMBURGER ========== */
.hamburger {
  width: 34px;
  height: 28px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  cursor: pointer;
  background: transparent;
  border: none;
}
.hamburger span {
  display: block;
  height: 3px;
  background: white;
  border-radius: 4px;
  transition: .3s ease;
}

.hamburger.active span:nth-child(1) {
  transform: translateY(10px) rotate(45deg);
}
.hamburger.active span:nth-child(2) {
  opacity: 0;
}
.hamburger.active span:nth-child(3) {
  transform: translateY(-10px) rotate(-45deg);
}

/* ========== CONTAINER ========== */
#container {
  flex: 1;
  display: flex;
  overflow: hidden;
}

/* ========== CLIENT LIST ========== */
#client-list {
  width: 300px;
  background: white;
  border-right: 2px solid #ccffcc;
  overflow-y: auto;
  padding: 10px;
  position: relative;
}

/* ========== REMINDER BANNERS ABOVE CLIENT LIST ========== */
#reminder-banner {
  background: var(--light-green);
  padding: 10px;
  border-radius: 8px;
  margin-bottom: 10px;
  border: 1px solid #b6eab6;
  font-size: 14px;
}

#reminder-banner strong {
  display: block;
  margin-bottom: 5px;
}

/* Search field */
#reminder-search {
  width: 100%;
  padding: 8px;
  border-radius: 8px;
  border: 1px solid #ccc;
}

/* ========== CLIENT ITEMS ========== */
.client-item {
  padding: 10px;
  border-radius: 10px;
  margin-bottom: 8px;
  background: #f8fff8;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.client-item:hover {
  background: #e2ffe2;
}

.client-item.active {
  background: #cfffcc;
  border-left: 5px solid var(--green);
}

/* Buttons */
.assign-btn {
  background: var(--green);
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 50%;
}

.unassign-btn {
  background: #cc0000;
  color: white;
  border-radius: 50%;
  padding: 5px 10px;
  border: none;
}

.locked-btn {
  background: #999;
  color: white;
  border-radius: 50%;
  padding: 5px 10px;
  border: none;
}

/* ========== CHAT AREA ========== */
#chat-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: white;
  position: relative;
}

#messages {
  flex: 1;
  overflow-y: auto;
  padding: 15px;
  position: relative;
}

#messages::before {
  content: "";
  position: absolute;
  top: 50%;
  left: 50%;
  width: 350px;
  height: 350px;
  background: url('<?= $logoPath ?>') no-repeat center center;
  background-size: contain;
  opacity: 0.08;
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.month-label {
  text-align: center;
  background: #eaffea;
  padding: 5px;
  border-radius: 8px;
  margin: 8px 0;
  color: #007700;
  font-weight: bold;
}

.bubble {
  max-width: 70%;
  margin: 8px 0;
  padding: 10px 14px;
  border-radius: 12px;
}

.client {
  background: #e9ffe9;
  float: left;
}

.csr {
  background: #cceaff;
  float: right;
}

.timestamp {
  display: block;
  font-size: 11px;
  color: #666;
  text-align: right;
}

/* ========== INPUT ROW ========== */
.input {
  display: flex;
  gap: 8px;
  padding: 10px;
  border-top: 1px solid #ddd;
}

.input input {
  flex: 1;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 8px;
}

.input button {
  background: var(--green);
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  font-weight: bold;
}

/* ========== RESPONSIVE ========== */
@media(max-width: 900px){
  .main {
    margin-left: 0;
  }
}
</style>

</head>
<body>

<!-- ========== SIDEBAR ========== -->
<div id="sidebar" class="closed">
  <h2><img src="<?= $logoPath ?>"> SkyTruFiber</h2>

  <a href="?tab=all">💬 Chat Dashboard</a>
  <a href="?tab=mine">👥 My Clients</a>
  <a href="#" onclick="showReminderView()">⏰ Reminders</a>
  <a href="survey_responses.php">📝 Survey Responses</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<!-- ========== MAIN CONTENT ========== -->
<div class="main">
  <div class="header">
    <button id="sidebar-toggle" class="hamburger">
      <span></span><span></span><span></span>
    </button>
    <div class="title">
      <img src="<?= $logoPath ?>" alt="logo">
      <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
  </div>

  <div id="container">

    <!-- ========== LEFT: CLIENT LIST + REMINDERS ABOVE ========== -->
    <div id="client-list">

      <div id="reminder-banner">
        <strong>Upcoming Reminders</strong>
        <input type="text" id="reminder-search" placeholder="Search reminders..." onkeyup="loadReminders()">
      </div>

      <!-- client list loads here -->
    </div>

    <!-- ========== RIGHT: CHAT AREA ========== -->
    <div id="chat-area">
      <div id="chat-header">
        <span id="chat-title">Select a client to view messages</span>
      </div>

      <div id="messages"></div>

      <div class="input" id="inputRow" style="display:none;">
        <input id="msg" placeholder="Type message…">
        <button onclick="sendMsg()">Send</button>
      </div>
    </div>

  </div>
</div>

<script>
let currentTab = "<?php echo $_GET['tab'] ?? 'all' ?>";
let clientId = 0;
const csrUser = "<?= htmlspecialchars($csr_user) ?>";
const csrFullname = "<?= htmlspecialchars($csr_fullname) ?>";

/* ==========================
   SIDEBAR TOGGLE
========================== */
const sidebar = document.getElementById('sidebar');
const main = document.querySelector('.main');
const toggleBtn = document.getElementById('sidebar-toggle');

toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('closed');
  toggleBtn.classList.toggle('active');

  if(window.innerWidth > 900){
    main.classList.toggle('shifted');
  }
});

/* ==========================
   LOAD CLIENTS
========================== */
function loadClients(){
  fetch(`csr_dashboard.php?ajax=clients&tab=${currentTab}`)
  .then(r=>r.text())
  .then(html=>{
    document.getElementById("client-list").innerHTML =
      document.getElementById("client-list").innerHTML.replace(document.getElementById("client-list").innerHTML, html);

    // Restore reminder banner
    document.getElementById("client-list").insertAdjacentHTML("afterbegin", `
      <div id="reminder-banner">
        <strong>Upcoming Reminders</strong>
        <input type="text" id="reminder-search" placeholder="Search reminders..." onkeyup="loadReminders()">
      </div>
    `);

    document.querySelectorAll(".client-item").forEach(el=>{
      el.onclick = ()=>selectClient(el);
    });

    loadReminders();
  });
}

function selectClient(el){
  document.querySelectorAll(".client-item").forEach(i=>i.classList.remove("active"));
  el.classList.add("active");

  let name = el.dataset.name;
  let assigned = el.dataset.csr;

  clientId = parseInt(el.dataset.id);

  document.getElementById('chat-title').textContent = "Chat with " + name;

  loadChat(assigned === csrUser);
}

/* ==========================
   LOAD CHAT
========================== */
function loadChat(canSend){
  if(!clientId) return;

  fetch(`../SKYTRUFIBER/load_chat.php?client_id=${clientId}`)
  .then(r=>r.json())
  .then(list=>{
    const box = document.getElementById("messages");
    box.innerHTML = "";

    let lastMonth = "";

    list.forEach(m=>{
      const d = new Date(m.time);
      const monthYear = d.toLocaleString("default",{month:"long"}) + " " + d.getFullYear();

      if(monthYear !== lastMonth){
        const label = document.createElement("div");
        label.className = "month-label";
        label.textContent = "📅 " + monthYear;
        box.appendChild(label);
        lastMonth = monthYear;
      }

      const bubble = document.createElement("div");
      bubble.className = "bubble " + (m.sender_type === "csr" ? "csr" : "client");

      bubble.innerHTML = `
        <strong>${m.sender_type === "csr" ? (m.csr_fullname || m.assigned_csr) : m.client_name}:</strong><br>
        ${m.message}
        <span class="timestamp">${new Date(m.time).toLocaleString()}</span>
      `;

      box.appendChild(bubble);
    });

    box.scrollTop = box.scrollHeight;

    document.getElementById("inputRow").style.display = canSend ? "flex" : "none";
  });
}

/* ==========================
   SEND MESSAGE
========================== */
function sendMsg(){
  const msg = document.getElementById("msg").value.trim();
  if(!msg) return;

  let form = new URLSearchParams();
  form.append("sender_type", "csr");
  form.append("message", msg);
  form.append("csr_user", csrUser);
  form.append("csr_fullname", csrFullname);
  form.append("client_id", clientId);

  fetch("../SKYTRUFIBER/save_chat.php", {
    method: "POST",
    headers: {"Content-Type":"application/x-www-form-urlencoded"},
    body: form
  }).then(()=>{
    document.getElementById("msg").value = "";
    loadChat(true);
  });
}

/* ==========================
   REMINDERS
========================== */
function loadReminders(){
  const s = document.getElementById("reminder-search").value;
  fetch(`csr_dashboard.php?ajax=load_reminders&search=${encodeURIComponent(s)}`)
  .then(r=>r.json())
  .then(rows=>{
    if(rows.length === 0){
      document.getElementById("reminder-banner").innerHTML = `
        <strong>Upcoming Reminders</strong>
        <input type="text" id="reminder-search" placeholder="Search reminders..." onkeyup="loadReminders()">
        <p style="text-align:center;color:#444;">No reminders</p>
      `;
      return;
    }

    let html = `
      <strong>Upcoming Reminders</strong>
      <input type="text" id="reminder-search" placeholder="Search reminders..." onkeyup="loadReminders()" value="${s}">
      <div style="margin-top:10px;max-height:200px;overflow-y:auto;">`;

    rows.forEach(r=>{
      html += `
        <div style="padding:8px;border-bottom:1px solid #ccc;">
          <b>${r.client_name}</b><br>
          Type: ${r.reminder_type}<br>
          Status: <span style="color:${r.status==='sent'?'green':'red'}">${r.status}</span><br>
          ${r.sent_at ? "Sent at: "+r.sent_at : ""}
        </div>
      `;
    });

    html += "</div>";

    document.getElementById("reminder-banner").innerHTML = html;
  });
}

function showReminderView(){
  document.getElementById("client-list").scrollTop = 0;
  loadReminders();
}

/* ==========================
   ASSIGN / UNASSIGN
========================== */
function assignClient(id){
  let form = new URLSearchParams();
  form.append("client_id", id);

  fetch("csr_dashboard.php?ajax=assign",{
    method:"POST",
    headers:{"Content-Type":"application/x-www-form-urlencoded"},
    body: form
  }).then(r=>r.text())
  .then(txt=>{
    if(txt==="ok"){
      alert("Client assigned!");
      loadClients();
    }
    else if(txt==="taken"){
      alert("Already assigned.");
      loadClients();
    }
  });
}

function unassignClient(id){
  if(!confirm("Unassign this client?")) return;

  let form = new URLSearchParams();
  form.append("client_id", id);

  fetch("csr_dashboard.php?ajax=unassign",{
    method:"POST",
    headers:{"Content-Type":"application/x-www-form-urlencoded"},
    body: form
  }).then(()=>{
    loadClients();
  });
}

/* ==========================
   REALTIME UPDATES
========================== */
window.onload = ()=>{
  sidebar.classList.add("closed");
  toggleBtn.classList.remove("active");

  loadClients();

  if(!!window.EventSource){
    const source = new EventSource("../SKYTRUFIBER/realtime_updates.php");
    source.addEventListener("update", ()=>{
      if(clientId) loadChat();
      loadClients();
    });
  } else {
    setInterval(()=>{
      if(clientId) loadChat();
      loadClients();
    }, 3000);
  }
};
</script>

</body>
</html>
