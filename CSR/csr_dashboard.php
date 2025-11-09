<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username=:u LIMIT 1");
$stmt->execute([":u"=>$csr_user]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $data['full_name'] ?? $csr_user;

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>

<style>
body {
    margin:0;
    font-family:Segoe UI, sans-serif;
    background:#f6fff6;
    overflow:hidden;
}

/* SIDEBAR OVERLAY — FIXED VERSION */
#overlay {
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.4);
    display:none;
    z-index:8;
}

/* SIDEBAR */
#sidebar {
    position:fixed;
    top:0;
    left:0;
    width:260px;
    height:100vh;
    background:#006b00;
    color:white;
    transform:translateX(-100%);
    transition:0.3s ease;
    z-index:9;
    box-shadow:5px 0 10px rgba(0,0,0,0.2);
}

#sidebar.active {
    transform:translateX(0);
}

#sidebar h2 {
    margin:0;
    padding:20px;
    background:#005c00;
    font-size:20px;
    text-align:center;
}

#sidebar a {
    display:block;
    padding:15px 20px;
    text-decoration:none;
    color:white;
    font-weight:500;
}

#sidebar a:hover {
    background:#009900;
}

/* HEADER */
header {
    height:60px;
    background:#009900;
    color:white;
    display:flex;
    align-items:center;
    padding:0 20px;
    font-size:22px;
    font-weight:600;
    justify-content:space-between;
}

#hamburger {
    cursor:pointer;
    font-size:28px;
    background:none;
    border:none;
    color:white;
    transition:transform 0.2s;
}

#hamburger.active {
    transform:rotate(90deg);
}

/* General tab style */
.tab {
    padding:10px 18px;
    border-radius:6px;
    cursor:pointer;
    font-weight:600;
    color:#006b00;
    background:#ffffff;
    border:2px solid transparent;
    transition:0.15s;
    display:flex;
    align-items:center;
}

/* Hover effect */
.tab:hover {
    background:#c8f5c8;
}

/* Active state – JS controlled tabs */
.tab.active {
    background:#006b00;
    color:white;
    border-color:#006400;
}

/* Styles for <a> tab links */
.tab-link {
    padding:10px 18px;
    border-radius:6px;
    font-weight:600;
    color:#006b00;
    background:#ffffff;
    border:2px solid transparent;
    text-decoration:none;
    display:flex;
    align-items:center;
    transition:0.15s ease;
}

/* Hover effect for link-style tabs */
.tab-link:hover {
    background:#c8f5c8;
}

/* Optional: when link is selected (active state) */
.tab-link.active {
    background:#006b00;
    color:white;
    border-color:#006400;
}


/* MAIN CONTENT */
#main {
    display:flex;
    height:calc(100vh - 105px);
}

/* LEFT CLIENT LIST */
#client-list {
    width:280px;
    overflow-y:auto;
    background:white;
    border-right:1px solid #d6d6d6;
    padding:10px;
}

/* CLIENT ITEM */
.client-item {
    padding:12px;
    margin-bottom:10px;
    border-radius:6px;
    background:#fff;
    box-shadow:0 0 6px rgba(0,0,0,0.1);
    cursor:pointer;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.client-item:hover {
    background:#e8ffe8;
}

/* CHAT AREA */
#chat-area {
    flex:1;
    display:flex;
    flex-direction:column;
    background:white;
}

#messages {
    flex:1;
    overflow-y:auto;
    padding:20px;
    position:relative;
}

#messages::before {
    content:"";
    position:absolute;
    top:50%; left:50%;
    width:500px;
    height:500px;
    opacity:0.06;
    background:url('<?= $logoPath ?>') no-repeat center;
    background-size:contain;
    transform:translate(-50%,-50%);
}

.message-bubble {
    max-width:70%;
    padding:12px;
    border-radius:10px;
    margin-bottom:12px;
    font-size:14px;
}

.client {
    background:#e4ffe4;
    align-self:flex-start;
}

.csr {
    background:#cfe9ff;
    align-self:flex-end;
}

/* INPUT ROW */
#input-row {
    padding:12px;
    border-top:1px solid #ccc;
    display:flex;
}

#input-row input {
    flex:1;
    padding:12px;
    border-radius:8px;
    border:1px solid #aaa;
}

#input-row button {
    padding:12px 20px;
    margin-left:10px;
    background:#009900;
    border:none;
    border-radius:8px;
    color:white;
    cursor:pointer;
}
</style>
</head>

<body>

<div id="overlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<div id="sidebar">
    <h2><img src="<?= $logoPath ?>" style="height:40px;"> Menu</h2>
    <a onclick="activateTab('clients')">💬 Chat Dashboard</a>
    <a onclick="activateTab('mine')">👤 My Clients</a>
    <a onclick="activateTab('reminders')">⏰ Reminders</a>
    <a href="survey_responses.php">📝 Survey Responses</a>
    <a href="edit_profile.php">👤 Edit Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<header>
    <button id="hamburger" onclick="toggleSidebar()">☰</button>
    <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
</header>

<div id="tabs">
    <!-- JavaScript switching tabs -->
    <div id="tab_clients" class="tab active" onclick="activateTab('clients')">💬 All Clients</div>
    <div id="tab_mine" class="tab" onclick="activateTab('mine')">👤 My Clients</div>
    <div id="tab_reminders" class="tab" onclick="activateTab('reminders')">⏰ Reminders</div>

    <!-- Link tabs -->
    <a href="survey_responses.php" id="tab_survey" class="tab-link">📝 Survey Responses</a>
    <a href="update_profile.php" id="tab_profile" class="tab-link">👤 Edit Profile</a>
</div>

<div id="main">

    <!-- CLIENT LIST -->
    <div id="client-list"></div>

    <!-- CHAT -->
    <div id="chat-area">
        <div id="messages">Select a client</div>

        <div id="input-row" style="display:none;">
            <input id="msg" placeholder="Type a reply...">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>

</div>

<script>
let currentTab = "clients";
let currentClient = null;

/* ✅ SIDEBAR OPEN/CLOSE FIX */
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const burger = document.getElementById("hamburger");

    const isOpen = sidebar.classList.contains("active");

    if (isOpen) {
        sidebar.classList.remove("active");
        overlay.style.display = "none";
        burger.classList.remove("active");
    } else {
        sidebar.classList.add("active");
        overlay.style.display = "block";
        burger.classList.add("active");
    }
}

/* ✅ TAB SWITCHING */
function activateTab(tab) {
    currentTab = tab;

    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    document.getElementById("tab_" + tab).classList.add("active");

    toggleSidebar();
    loadClients();
}

/* ✅ LOAD CLIENTS */
function loadClients() {
    fetch(`csr_dashboard.php?ajax=clients&tab=${currentTab}`)
    .then(r => r.text())
    .then(html => {
        document.getElementById("client-list").innerHTML = html;
        document.querySelectorAll(".client-item").forEach(item => {
            item.onclick = () => selectClient(item);
        });
    });
}

/* ✅ SELECT CLIENT */
function selectClient(item) {
    currentClient = item.dataset.id;
    document.getElementById("messages").innerHTML = "";
    document.getElementById("input-row").style.display = "flex";

    loadChat();
}

/* ✅ LOAD CHAT */
function loadChat() {
    fetch(`csr_dashboard.php?ajax=load_chat&client_id=${currentClient}`)
    .then(r => r.json())
    .then(rows => {
        let box = document.getElementById("messages");
        box.innerHTML = "";

        rows.forEach(m => {
            let bubble = document.createElement("div");
            bubble.className = `message-bubble ${m.sender_type}`;
            bubble.innerHTML = `<strong>${m.sender_type === 'csr' ? m.csr_fullname : m.client_name}:</strong> ${m.message}`;
            box.appendChild(bubble);
        });

        box.scrollTop = box.scrollHeight;
    });
}

/* ✅ SEND MESSAGE */
function sendMessage() {
    const msg = document.getElementById("msg").value.trim();
    if (!msg) return;

    fetch("../SKYTRUFIBER/save_chat.php", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`client_id=${currentClient}&message=${encodeURIComponent(msg)}&sender_type=csr`
    }).then(() => {
        document.getElementById("msg").value="";
        loadChat();
    });
}

/* ✅ AUTO REFRESH CLIENT LIST & CHAT */
setInterval(() => {
    loadClients();
    if (currentClient) loadChat();
}, 5000);

loadClients();
</script>

</body>
</html>
