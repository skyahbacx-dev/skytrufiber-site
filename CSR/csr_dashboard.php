<?php
session_start();
include '../db_connect.php';

// --- Auth Guard ---
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Fetch CSR info
$stmt = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$csr = $stmt->fetch(PDO::FETCH_ASSOC);

$csr_fullname = $csr['full_name'] ?? $csr_user;
$csr_email     = $csr['email'] ?? "";

// Logo fallback
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<style>
/* --------------------------------------------
   ORIGINAL CLEAN STYLE RESTORED & IMPROVED
   -------------------------------------------- */

body {
    margin:0; padding:0;
    font-family: "Segoe UI", Arial, sans-serif;
    background:#f2fff2;
    overflow:hidden;
    height:100vh;
}

/* Background Logo Watermark */
body::before {
    content:"";
    position:absolute;
    top:50%; left:50%;
    width:500px; height:500px;
    background:url('<?= $logoPath ?>') no-repeat center center;
    background-size:contain;
    opacity:0.07;
    transform:translate(-50%,-50%);
    pointer-events:none;
}

/* HEADER */
header {
    height:60px;
    background:#009900;
    color:white;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 20px;
    position:relative;
    z-index:5;
}

header .title img {
    height:40px;
    margin-right:10px;
}

/* HAMBURGER */
#hamburger {
    cursor:pointer;
    font-size:24px;
    transition:0.3s ease;
}
#hamburger.active {
    transform:rotate(90deg);
}

/* SIDEBAR */
#sidebar {
    width:240px;
    background:#006600;
    color:white;
    position:absolute;
    top:60px;
    bottom:0;
    left:0;
    transition:0.3s ease;
    overflow-y:auto;
    z-index:4;
}

#sidebar.collapsed {
    transform:translateX(-240px);
}

#sidebar a {
    display:block;
    padding:14px 20px;
    color:white;
    text-decoration:none;
    font-weight:600;
    border-bottom:1px solid rgba(255,255,255,0.1);
}

#sidebar a:hover {
    background:#00aa00;
}

/* TAB BAR */
#tabs {
    display:flex;
    background:#eaffea;
    padding:10px 20px;
    gap:15px;
    border-bottom:1px solid #ccc;
}

.tab {
    padding:8px 16px;
    background:white;
    border-radius:8px;
    cursor:pointer;
    font-weight:700;
    color:#009900;
    border:2px solid #009900;
}

.tab.active {
    background:#009900;
    color:white;
}

/* MAIN LAYOUT */
#main {
    margin-left:240px;
    transition:margin-left 0.3s ease;
}

#main.shifted {
    margin-left:0;
}

/* TWO Column layout */
#container {
    display:flex;
    height:calc(100vh - 120px);
}

/* LEFT Client List */
#client-list {
    width:300px;
    background:white;
    border-right:1px solid #ccc;
    overflow-y:auto;
}

.client-item {
    padding:12px;
    border-bottom:1px solid #eee;
    cursor:pointer;
}

.client-item:hover {
    background:#eaffea;
}

.client-item.active {
    background:#c8f8c8;
    font-weight:700;
}

.assign-btn, .unassign-btn {
    float:right;
    background:#009900;
    color:white;
    border:none;
    border-radius:50%;
    padding:5px 8px;
    cursor:pointer;
}
.unassign-btn { background:#cc0000; }

/* Chat Area */
#chat-area {
    flex:1;
    display:flex;
    flex-direction:column;
    position:relative;
}

#chat-header {
    background:#009900;
    color:white;
    padding:10px;
    font-weight:700;
}

#messages {
    flex:1;
    overflow-y:auto;
    padding:20px;
    position:relative;
}

.bubble {
    max-width:70%;
    margin:8px 0;
    padding:10px 14px;
    border-radius:10px;
}
.client { background:#e9ffe9; float:left; }
.csr    { background:#ccf0ff; float:right; }

.timestamp {
    display:block;
    font-size:11px;
    color:#777;
    margin-top:4px;
    text-align:right;
}

/* Input */
.input {
    display:flex;
    padding:10px;
    background:white;
    border-top:1px solid #ccc;
}
.input input {
    flex:1;
    padding:12px;
    border-radius:6px;
    border:1px solid #ccc;
}
.input button {
    margin-left:10px;
    background:#009900;
    color:white;
    border:none;
    padding:12px 18px;
    border-radius:6px;
    font-weight:bold;
    cursor:pointer;
}

/* Reminder POPUP */
#reminder-popup {
    position:absolute;
    right:30px;
    top:80px;
    width:300px;
    background:white;
    border-left:5px solid #ffcc00;
    box-shadow:0 0 12px rgba(0,0,0,0.2);
    padding:15px;
    display:none;
    z-index:100;
}

#reminder-popup h4 {
    margin:0 0 10px 0;
    color:#009900;
}

#reminder-popup button {
    background:#009900;
    color:white;
    border:none;
    padding:6px 10px;
    border-radius:6px;
    cursor:pointer;
}
#reminder-popup .close {
    background:#cc0000;
}

/* Reminder TAB Page */
#reminders-page {
    display:none;
    padding:20px;
    overflow-y:auto;
    background:white;
    border-left:1px solid #ccc;
}

.search-input {
    width:300px;
    padding:10px;
    border:1px solid #009900;
    border-radius:6px;
}

.reminder-row {
    padding:10px;
    margin:5px 0;
    border-bottom:1px solid #ddd;
}

</style>
</head>

<body>

<header>
    <div id="hamburger">☰</div>
    <div class="title">
        <img src="<?= $logoPath ?>" alt="">
        <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
</header>

<!-- SIDEBAR -->
<div id="sidebar">
    <a onclick="setTab('all')">💬 All Clients</a>
    <a onclick="setTab('mine')">👥 My Clients</a>
    <a onclick="openReminderPage()">⏰ Reminders</a>
    <a onclick="window.location='survey_responses.php'">📄 Surveys & Feedback</a>
    <a onclick="openProfile()">👤 Edit Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<!-- MAIN -->
<div id="main">

    <!-- TOP TABS -->
    <div id="tabs">
        <div class="tab" id="tab-all" onclick="setTab('all')">💬 All Clients</div>
        <div class="tab" id="tab-mine" onclick="setTab('mine')">👥 My Clients</div>
        <div class="tab" onclick="openReminderPage()">⏰ Reminders</div>
        <div class="tab" onclick="window.location='survey_responses.php'">📄 Surveys & Feedback</div>
    </div>

    <div id="container">

        <!-- CLIENT LIST -->
        <div id="client-list"></div>

        <!-- CHAT AREA -->
        <div id="chat-area">
            <div id="chat-header">Select a client to view messages</div>
            <div id="messages"></div>

            <div class="input" id="inputRow" style="display:none;">
                <input id="msg" placeholder="Type your reply…">
                <button onclick="sendMsg()">Send</button>
            </div>
        </div>

        <!-- REMINDER PAGE -->
        <div id="reminders-page">
            <h2>⏰ Reminder List</h2>
            <input class="search-input" id="rSearch" placeholder="Search reminders…" onkeyup="searchReminders()">
            <div id="reminderList"></div>
        </div>

    </div>
</div>

<!-- Popup Reminder -->
<div id="reminder-popup">
    <h4>Upcoming Reminder</h4>
    <div id="popup-text"></div>
    <br>
    <button class="close" onclick="closePopup()">Close</button>
</div>


<script>

// -----------------------------
// Sidebar collapse
// -----------------------------

const sidebar = document.getElementById("sidebar");
const main = document.getElementById("main");
const hamburger = document.getElementById("hamburger");

hamburger.onclick = () => {
    sidebar.classList.toggle("collapsed");
    main.classList.toggle("shifted");
    hamburger.classList.toggle("active");
};


// -----------------------------
// Tabs & page switching
// -----------------------------

let currentTab = 'all';
let clientId = 0;
const csrUser = "<?= htmlspecialchars($csr_user) ?>";
const csrFullname = "<?= htmlspecialchars($csr_fullname) ?>";

function setTab(tab) {
    currentTab = tab;
    document.getElementById("reminders-page").style.display = "none";
    document.getElementById("chat-area").style.display = "block";
    loadClients();

    document.getElementById("tab-all").classList.toggle("active", tab === "all");
    document.getElementById("tab-mine").classList.toggle("active", tab === "mine");
}

function openReminderPage() {
    document.getElementById("chat-area").style.display = "none";
    document.getElementById("reminders-page").style.display = "block";
    loadReminders();
}


// -----------------------------
// Load Clients
// -----------------------------
function loadClients() {
    fetch("csr_dashboard.php?ajax=clients&tab=" + currentTab)
        .then(r => r.text())
        .then(html => {
            document.getElementById("client-list").innerHTML = html;

            document.querySelectorAll(".client-item").forEach(item => {
                item.onclick = () => selectClient(item);
            });
        });
}

function selectClient(el) {
    const assigned = el.dataset.csr;
    clientId = parseInt(el.dataset.id);

    document.querySelectorAll(".client-item").forEach(i => i.classList.remove("active"));
    el.classList.add("active");

    document.getElementById("chat-header").textContent = "Chat with " + el.dataset.name;
    loadChat(assigned === csrUser);
}


// -----------------------------
// Load Chat
// -----------------------------
function loadChat(isMine) {
    fetch("../SKYTRUFIBER/load_chat.php?client_id=" + clientId)
        .then(r => r.json())
        .then(data => {
            const box = document.getElementById("messages");
            box.innerHTML = "";

            data.forEach(msg => {
                let div = document.createElement("div");
                div.className = "bubble " + (msg.sender_type === "csr" ? "csr" : "client");
                div.innerHTML = msg.message + "<span class='timestamp'>" +
                    new Date(msg.time).toLocaleString() + "</span>";
                box.appendChild(div);
            });

            box.scrollTop = box.scrollHeight;
            document.getElementById("inputRow").style.display = isMine ? "flex" : "none";
        });
}


// -----------------------------
// Send Message
// -----------------------------
function sendMsg() {
    let msg = document.getElementById("msg").value.trim();
    if (!msg || !clientId) return;

    let form = new URLSearchParams();
    form.append("message", msg);
    form.append("sender_type", "csr");
    form.append("csr_user", csrUser);
    form.append("csr_fullname", csrFullname);
    form.append("client_id", clientId);

    fetch("../SKYTRUFIBER/save_chat.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: form
    }).then(() => {
        document.getElementById("msg").value = "";
        loadChat(true);
    });
}


// -----------------------------
// Reminders (Popup + List)
// -----------------------------

function showPopup(text) {
    document.getElementById("popup-text").textContent = text;
    document.getElementById("reminder-popup").style.display = "block";
}

function closePopup() {
    document.getElementById("reminder-popup").style.display = "none";
}

function checkReminders() {
    fetch("csr_dashboard.php?ajax=upcoming_reminders")
        .then(r => r.json())
        .then(list => {
            if (list.length > 0) {
                showPopup(list[0].message);
            }
        });
}

// run every 15 sec
setInterval(checkReminders, 15000);


// Reminder Page
function loadReminders() {
    fetch("csr_dashboard.php?ajax=load_all_reminders")
        .then(r => r.json())
        .then(rows => {
            let out = "";
            rows.forEach(r => {
                out += `<div class="reminder-row">
                    <b>${r.client_name}</b> — ${r.reminder_type}
                    <div>Status: ${r.status}</div>
                    <div><small>Sent: ${r.sent_at || ''}</small></div>
                </div>`;
            });
            document.getElementById("reminderList").innerHTML = out;
        });
}

function searchReminders() {
    let val = document.getElementById("rSearch").value.toLowerCase();
    const rows = document.querySelectorAll(".reminder-row");
    rows.forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(val) ? "" : "none";
    });
}


// -----------------------------
// Real-time updates
// -----------------------------
if (!!window.EventSource) {
    const evt = new EventSource("../SKYTRUFIBER/realtime_updates.php");
    evt.addEventListener("update", () => {
        if (clientId) loadChat();
        loadClients();
    });
}
</script>

</body>
</html>
