<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// GET CSR DETAILS
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u'=>$csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
<link rel="stylesheet" href="csr_dashboard.css">
</head>

<body>

<!-- SIDEBAR -->
<div id="sidebar">
    <button id="closeSidebar" onclick="toggleSidebar()">✖</button>
    <h2>CSR Menu</h2>
    <a onclick="switchTab('all')">💬 All Clients</a>
    <a onclick="switchTab('mine')">👤 My Clients</a>
    <a onclick="switchTab('rem')">⏰ Reminders</a>
    <a href="survey_responses.php">📝 Survey Responses</a>
    <a href="update_profile.php">👤 Edit Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- HEADER -->
<header>
    <button id="hamb" onclick="toggleSidebar()">☰</button>
    <div class="brand">
        <img src="<?= $logoPath ?>" alt="Logo">
        <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
</header>

<!-- TABS -->
<div id="tabs">
    <div id="tab-all" class="tab active" onclick="switchTab('all')">💬 All Clients</div>
    <div id="tab-mine" class="tab" onclick="switchTab('mine')">👤 My Clients</div>
    <div id="tab-rem" class="tab" onclick="switchTab('rem')">⏰ Reminders</div>
</div>

<!-- MAIN GRID -->
<div id="main">

    <!-- LEFT: CLIENTS -->
    <div id="client-col"></div>

    <!-- RIGHT: CHAT -->
    <div id="chat-col">

        <button id="collapseBtn" onclick="collapseChat()">●</button>

        <div id="chat-head">
            <div class="chat-title">
                <div id="chatAvatar" class="avatar"></div>
                <div>
                    <div id="chat-name">Select a client</div>
                    <div id="status" class="status">Offline</div>
                </div>
            </div>
            <div class="info-dot">i</div>
        </div>

        <div id="messages"></div>

        <div id="typingIndicator" style="display:none;">Typing...</div>

        <div id="input" style="display:none;">
            <input id="msg" placeholder="Type a reply…" onkeyup="typing()">
            <button onclick="sendMsg()">Send</button>
        </div>

        <!-- REMINDERS -->
        <div id="reminders">
            <input id="rem-q" placeholder="Search…" onkeyup="loadReminders()">
            <div id="rem-list"></div>
        </div>

    </div>

</div>

<script>
let currentTab = "all";
let currentClient = 0;
let currentAssignee = "";
const csrUser = <?= json_encode($csr_user) ?>;

// Sidebar
function toggleSidebar() {
    const s = document.getElementById("sidebar");
    const o = document.getElementById("sidebar-overlay");
    const open = s.classList.contains("active");
    if (open) {
        s.classList.remove("active");
        o.style.display = "none";
    } else {
        s.classList.add("active");
        o.style.display = "block";
    }
}

// Collapse chat
function collapseChat() {
    const col = document.getElementById("chat-col");
    col.classList.toggle("collapsed");
}

// Switch tabs
function switchTab(tab) {
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    document.getElementById("tab-"+tab).classList.add("active");

    currentTab = tab;

    if (tab === "rem") {
        document.getElementById("chat-head").style.display = "none";
        document.getElementById("messages").style.display = "none";
        document.getElementById("input").style.display = "none";
        document.getElementById("typingIndicator").style.display="none";
        document.getElementById("reminders").style.display = "block";
        loadReminders();
    } else {
        document.getElementById("reminders").style.display = "none";
        document.getElementById("chat-head").style.display = "flex";
        document.getElementById("messages").style.display = "block";
        loadClients();
    }
}

// Load clients
function loadClients() {
    fetch("csr_ajax.php?clients=1&tab="+currentTab)
    .then(r=>r.text())
    .then(html=>{
        document.getElementById("client-col").innerHTML = html;
        document.querySelectorAll(".client-item").forEach(el=>{
            el.addEventListener("click", ()=>selectClient(el));
        });
    });
}

// Select client
function selectClient(el) {
    currentClient = el.dataset.id;
    currentAssignee = el.dataset.csr;
    const name = el.dataset.name;

    document.getElementById("chat-name").textContent = name;
    document.getElementById("input").style.display = "flex";

    fetch("csr_ajax.php?client_profile=1&name="+encodeURIComponent(name))
    .then(r=>r.json())
    .then(p=>{
        setAvatar(name, p.gender, p.avatar);
        document.getElementById("status").textContent = p.online ? "Online" : "Offline";
    });

    loadChat();
}

// Avatar
function setAvatar(name, gender, avatar) {
    const box = document.getElementById("chatAvatar");
    box.innerHTML = "";
    let img = document.createElement("img");

    if (avatar) {
        img.src = "../uploads/"+avatar;
    } else if (gender === "female") {
        img.src = "../penguin.png";
    } else if (gender === "male") {
        img.src = "../lion.png";
    } else {
        box.textContent = name.split(" ").map(a=>a[0]).join("").toUpperCase();
        return;
    }
    box.appendChild(img);
}

// Load chat
function loadChat() {
    if (!currentClient) return;
    fetch("csr_ajax.php?load_chat=1&client_id="+currentClient)
    .then(r=>r.json())
    .then(rows=>{
        const box = document.getElementById("messages");
        box.innerHTML = "";

        rows.forEach(m=>{
            box.innerHTML += `
            <div class="msg ${m.sender}">
                <div class="bubble">
                    <strong>${m.sender === "csr" ? "CSR <?= htmlspecialchars($csr_fullname) ?>" : m.client}:</strong>
                    ${m.message}
                </div>
                <div class="meta">${m.time} ${m.read?"✅":""}</div>
            </div>`;
        });

        box.scrollTop = box.scrollHeight;
    });
}

// Send message
function sendMsg() {
    if (!currentClient) return;

    if (currentAssignee !== csrUser && currentAssignee !== "Unassigned") {
        alert("This client is assigned to another CSR.");
        return;
    }

    const msg = document.getElementById("msg").value.trim();
    if (!msg) return;

    fetch("csr_ajax.php?send=1", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"msg="+encodeURIComponent(msg)+"&client_id="+currentClient
    }).then(()=>{
        document.getElementById("msg").value = "";
        loadChat();
    });
}

// Typing indicator
function typing() {
    fetch("csr_ajax.php?typing=1&client_id="+currentClient);
}

// Load reminders
function loadReminders() {
    const q = document.getElementById("rem-q").value;
    fetch("csr_ajax.php?reminders=1&q="+encodeURIComponent(q))
    .then(r=>r.json())
    .then(list=>{
        const box = document.getElementById("rem-list");
        box.innerHTML = "";
        if (!list.length) {
            box.innerHTML = "<div>No reminders</div>";
            return;
        }
        list.forEach(a=>{
            box.innerHTML += `
            <div class="rem-card">
                <b>${a.name}</b> (${a.email})<br>
                Due: ${a.due}<br>
                ${a.badges}
            </div>`;
        });
    });
}

// Auto refresh
setInterval(()=>{
    if (currentClient) loadChat();
}, 3000);

// Init load
loadClients();

</script>

</body>
</html>
