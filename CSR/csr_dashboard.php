<?php
session_start();
include '../db_connect.php';

// ✅ Ensure CSR is logged in
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// ✅ Fetch CSR full profile details
$stmt = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([":u" => $csr_user]);
$csrData = $stmt->fetch(PDO::FETCH_ASSOC);

$csr_fullname = $csrData["full_name"] ?? $csr_user;
$csr_email     = $csrData["email"] ?? "";

// ✅ Logo path fallback
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ================================
   ✅ AJAX Handler Responses
================================ */
if (isset($_GET["ajax"])) {

    // --------------- Load Client List ---------------
    if ($_GET["ajax"] == "clients") {
        $tab = $_GET["tab"] ?? "all";

        if ($tab === "mine") {
            $stmt = $conn->prepare("
                SELECT c.id, c.name, c.assigned_csr,
                MAX(ch.created_at) AS last_chat
                FROM clients c
                LEFT JOIN chat ch ON c.id = ch.client_id
                WHERE c.assigned_csr = :csr
                GROUP BY c.id, c.name, c.assigned_csr
                ORDER BY last_chat DESC NULLS LAST
            ");
            $stmt->execute([":csr" => $csr_user]);
        } else {
            $stmt = $conn->query("
                SELECT c.id, c.name, c.assigned_csr,
                MAX(ch.created_at) AS last_chat
                FROM clients c
                LEFT JOIN chat ch ON c.id = ch.client_id
                GROUP BY c.id, c.name, c.assigned_csr
                ORDER BY last_chat DESC NULLS LAST
            ");
        }

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $assigned = $row["assigned_csr"] ?: "Unassigned";
            $owned = ($assigned === $csr_user);

            echo "
                <div class='client-item' data-id='{$row['id']}' data-name='".htmlspecialchars($row['name'], ENT_QUOTES)."' data-csr='{$assigned}'>
                    <div class='client-info'>
                        <strong>".htmlspecialchars($row['name'])."</strong>
                        <small>Assigned: {$assigned}</small>
                    </div>
                    <div class='actions'>
            ";

            if ($assigned === "Unassigned") {
                echo "<button class='assign' onclick='assignClient({$row['id']})'>＋</button>";
            } elseif ($assigned === $csr_user) {
                echo "<button class='unassign' onclick='unassignClient({$row['id']})'>−</button>";
            } else {
                echo "<button class='locked' disabled>🔒</button>";
            }

            echo "</div></div>";
        }

        exit;
    }

    // --------------- Load Chat Messages ---------------
    if ($_GET["ajax"] == "load_chat" && isset($_GET["client_id"])) {
        $cid = (int)$_GET["client_id"];

        $stmt = $conn->prepare("
            SELECT sender_type, message, created_at, csr_fullname, client_name
            FROM chat
            WHERE client_id = :cid
            ORDER BY created_at ASC
        ");
        $stmt->execute([":cid" => $cid]);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // --------------- Reminders List ---------------
    if ($_GET["ajax"] == "load_reminders") {
        $search = "%".($_GET["search"] ?? "")."%";

        $stmt = $conn->prepare("
        SELECT r.id, r.reminder_type, r.status, r.sent_at, r.send_on, c.name AS client_name
        FROM reminders r
        LEFT JOIN clients c ON r.client_id = c.id
        WHERE c.name ILIKE :s OR r.reminder_type ILIKE :s
        ORDER BY r.send_on ASC
        ");
        $stmt->execute([":s"=>$search]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — SkyTruFiber</title>

<style>
/* ✅ ORIGINAL GREEN THEME, CLEAN + FLEXIBLE LAYOUT */

body {
    margin: 0;
    padding: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background:#f2fff2;
    overflow:hidden;
}

/* ✅ Collapsible Sidebar ----------------------*/
#sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height:100%;
    background:#009900;
    color:white;
    transform: translateX(-100%);
    transition: 0.3s;
    z-index:10;
}

#sidebar.active {
    transform: translateX(0);
}

#sidebar h2 {
    background:#007a00;
    margin:0;
    padding:20px;
    text-align:center;
}

#sidebar a {
    display: block;
    padding:15px 20px;
    text-decoration:none;
    color:white;
    font-weight:600;
}

#sidebar a:hover {
    background:#00b300;
}

/* ✅ Main Header ------------------------*/
header {
    background:#009900;
    color:white;
    padding:15px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

#hamburger {
    font-size:28px;
    cursor:pointer;
    border:none;
    background:none;
    color:white;
}
#hamburger.active { transform:rotate(90deg); }

/* ✅ Tabs -------------------------*/
#tabs {
    display:flex;
    gap:10px;
    padding:10px 20px;
    background:#eaffea;
    border-bottom:1px solid #ccc;
}

.tab {
    padding:10px 20px;
    border-radius:8px;
    font-weight:700;
    cursor:pointer;
    color:#007a00;
}

.tab.active {
    background:#009900;
    color:white;
}

/* ✅ Layout Columns ------------------------*/
#container {
    display:flex;
    height:calc(100vh - 110px);
}

#client-list {
    width:300px;
    overflow-y:auto;
    background:white;
    border-right:1px solid #ccc;
    padding:10px;
}

/* ✅ Client List Items -----------------------*/
.client-item {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:10px;
    margin-bottom:8px;
    background:white;
    border-radius:8px;
    cursor:pointer;
    box-shadow:0 1px 4px rgba(0,0,0,0.1);
}

.client-item:hover {
    background:#e6ffe6;
}

.client-item.active {
    background:#c8f8c8;
    font-weight:bold;
}

/* ✅ Chat area -------------------------------*/
#chat-area {
    flex:1;
    display:flex;
    flex-direction:column;
    background:white;
    position:relative;
}

#messages {
    flex:1;
    overflow-y:auto;
    padding:15px;
    position:relative;
}

#messages::before {
    content:"";
    position:absolute;
    top:50%;
    left:50%;
    width:500px;
    height:500px;
    background:url('<?= $logoPath ?>') no-repeat center center;
    background-size:contain;
    opacity:0.05;
    transform:translate(-50%, -50%);
}

/* ✅ Bubbles ------------------------------*/
.bubble {
    padding:12px 15px;
    border-radius:12px;
    margin-bottom:10px;
    max-width:70%;
    font-size:14px;
}

.client {
    background:#e9ffe9;
    align-self:flex-start;
}

.csr {
    background:#ccf0ff;
    align-self:flex-end;
}

/* ✅ Input Bar -----------------------------*/
.input {
    display:flex;
    padding:10px;
    border-top:1px solid #ccc;
}

.input input {
    flex:1;
    padding:10px;
    border-radius:8px;
    border:1px solid #ccc;
}

.input button {
    padding:10px 20px;
    background:#009900;
    border:none;
    color:white;
    margin-left:10px;
    border-radius:8px;
    font-size:16px;
    cursor:pointer;
}

/* ✅ Reminders Panel ----------------------*/
#reminders-panel {
    display:none;
    padding:20px;
}

.searchbox {
    padding:8px;
    border:1px solid #ccc;
    width:250px;
    border-radius:8px;
    margin-bottom:10px;
}

</style>
</head>

<body>

<!-- ✅ Sidebar -->
<div id="sidebar">
  <h2><img style="height:40px;" src="<?= $logoPath ?>"> Menu</h2>
  <a href="#" onclick="switchTab('clients')">💬 Chat Dashboard</a>
  <a href="#" onclick="switchTab('mine')">🧑‍💼 My Clients</a>
  <a href="#" onclick="openReminderTab()">⏰ Reminders</a>
  <a href="edit_profile.php">👤 Edit Profile</a>
  <a href="survey_responses.php">📝 Survey Responses</a>
  <a href="csr_logout.php">🚪 Logout</a>
</div>

<!-- ✅ Main Content -->
<div id="main-content">

<header>
  <button id="hamburger" onclick="toggleSidebar()">☰</button>
  <h1>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></h1>
</header>

<div id="tabs">
    <div class="tab active" id="tab_clients" onclick="switchTab('clients')">💬 All Clients</div>
    <div class="tab" id="tab_mine" onclick="switchTab('mine')">👤 My Clients</div>
    <div class="tab" id="tab_reminders" onclick="openReminderTab()">⏰ Reminders</div>
</div>

<div id="container">

<!-- ✅ Clients List -->
<div id="client-list"></div>

<!-- ✅ Chat Panel -->
<div id="chat-area">
  <div id="chat-header" class="header">
    <h3 id="chat-title">Select a client</h3>
  </div>
  <div id="messages"></div>

  <div class="input" id="inputRow" style="display:none;">
    <input type="text" id="msg" placeholder="Type a message...">
    <button onclick="sendMsg()">Send</button>
  </div>
</div>

<!-- ✅ Reminders Tab -->
<div id="reminders-panel">
    <input class="searchbox" placeholder="Search reminders..." onkeyup="loadReminders(this.value)">
    <div id="reminders-list"></div>
</div>

</div><!-- End container -->

</div><!-- End main -->

<script>
let currentTab = "clients";
let clientId = null;

/* ✅ Sidebar Toggle */
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("hamburger").classList.toggle("active");
}

/* ✅ Switch Tabs (Clients / My Clients / Reminders) */
function switchTab(tab) {
    currentTab = tab;
    document.getElementById("chat-area").style.display = "block";
    document.getElementById("reminders-panel").style.display = "none";

    document.querySelectorAll(".tab").forEach(t=>t.classList.remove("active"));
    document.getElementById("tab_"+tab).classList.add("active");

    loadClients();
}

/* ✅ Reminders Tab */
function openReminderTab() {
    document.querySelectorAll(".tab").forEach(t=>t.classList.remove("active"));
    document.getElementById("tab_reminders").classList.add("active");

    document.getElementById("chat-area").style.display = "none";
    document.getElementById("reminders-panel").style.display = "block";

    loadReminders("");
}

/* ✅ Load Clients List */
function loadClients() {
    fetch(`csr_dashboard.php?ajax=clients&tab=${currentTab}`)
    .then(res => res.text())
    .then(html => {
        document.getElementById("client-list").innerHTML = html;
        document.querySelectorAll(".client-item").forEach(item => {
            item.onclick = () => selectClient(item);
        });
    });
}

/* ✅ Select Client */
function selectClient(item) {
    clientId = item.dataset.id;
    document.getElementById("chat-title").textContent = "Chat with " + item.dataset.name;
    document.getElementById("reminders-panel").style.display = "none";
    loadChat();
    document.getElementById("inputRow").style.display = "flex";
}

/* ✅ Load Chat Messages */
function loadChat() {
    if (!clientId) return;
    fetch(`csr_dashboard.php?ajax=load_chat&client_id=${clientId}`)
    .then(res => res.json())
    .then(messages => {
        let box = document.getElementById("messages");
        box.innerHTML = "";

        messages.forEach(m => {
            let div = document.createElement("div");
            div.className = `bubble ${m.sender_type}`;
            div.innerHTML = `<strong>${m.sender_type == 'csr' ? m.csr_fullname : m.client_name}:</strong> ${m.message}`;
            box.appendChild(div);
        });

        box.scrollTop = box.scrollHeight;
    });
}

/* ✅ Send Message */
function sendMsg() {
    let text = document.getElementById("msg").value.trim();
    if (!text) return;

    fetch("../SKYTRUFIBER/save_chat.php", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`client_id=${clientId}&message=${encodeURIComponent(text)}&sender_type=csr`
    })
    .then(() => {
        document.getElementById("msg").value = "";
        loadChat();
    });
}

/* ✅ Load reminders */
function loadReminders(search) {
    fetch(`csr_dashboard.php?ajax=load_reminders&search=${search}`)
    .then(r=>r.json())
    .then(rows=>{
        let out = "<table width='100%' style='border-collapse:collapse;'>";
        out += "<tr><th>ID</th><th>Client</th><th>Type</th><th>Status</th><th>Send On</th><th>Sent At</th></tr>";

        rows.forEach(r=>{
            out += `
                <tr>
                    <td>${r.id}</td>
                    <td>${r.client_name}</td>
                    <td>${r.reminder_type}</td>
                    <td>${r.status}</td>
                    <td>${r.send_on}</td>
                    <td>${r.sent_at || ""}</td>
                </tr>
            `;
        });

        out += "</table>";
        document.getElementById("reminders-list").innerHTML = out;
    });
}

/* ✅ Auto-refresh chat + client list */
setInterval(() => {
    if (clientId) loadChat();
    loadClients();
}, 6000);

/* ✅ Initial Load */
window.onload = loadClients;
</script>

</body>
</html>
