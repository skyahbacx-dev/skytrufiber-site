<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Load CSR fullname
$stmt = $conn->prepare("SELECT full_name, email, profile_pic FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$csr = $stmt->fetch(PDO::FETCH_ASSOC);

$csr_fullname = $csr['full_name'] ?? $csr_user;
$profile_pic = $csr['profile_pic'] ?? "../SKYTRUFIBER/default.png";

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
    <link rel="stylesheet" href="csr_dashboard.css">
</head>
<body>

<!-- Sidebar Overlay -->
<div id="sidebar-overlay" onclick="toggleSidebar(false)"></div>

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <h2>Menu</h2>
        <button class="close-sidebar" onclick="toggleSidebar(false)">✖</button>
    </div>

    <a onclick="switchTab('all')">💬 All Clients</a>
    <a onclick="switchTab('mine')">👤 My Clients</a>
    <a onclick="switchTab('rem')">⏰ Reminders</a>
    <a href="survey_responses.php">📝 Surveys</a>
    <a href="update_profile.php">👤 Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<!-- Header -->
<header class="header">
    <button class="hamburger" onclick="toggleSidebar(true)">☰</button>
    <div class="header-brand">
        <img src="../SKYTRUFIBER/AHBALOGO.png" class="header-logo">
        <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
</header>

<!-- Tabs -->
<div class="tabs">
    <div id="tab-all" class="tab active" onclick="switchTab('all')">💬 All Clients</div>
    <div id="tab-mine" class="tab" onclick="switchTab('mine')">👤 My Clients</div>
    <div id="tab-rem" class="tab" onclick="switchTab('rem')">⏰ Reminders</div>
    <div class="tab" onclick="location.href='survey_responses.php'">📝 Surveys</div>
    <div class="tab" onclick="location.href='update_profile.php'">👤 Profile</div>
</div>

<!-- Main Layout -->
<div id="main">

    <!-- Client list column -->
    <div id="client-col"></div>

    <!-- Chat column -->
    <div id="chat-col">

        <div id="chat-head" class="chat-head">
            <div class="chat-title">
                <div id="chatAvatar" class="avatar"></div>
                <span id="chat-title-text">Select a client</span>
            </div>
        </div>

        <div id="messages" class="messages"></div>

        <div id="input" class="chat-input">
            <input id="msg" placeholder="Type a reply…">
            <button onclick="sendMsg()">Send</button>
        </div>

        <!-- Reminders -->
        <div id="reminders" class="reminders-panel" style="display:none;">
            <input id="rem-q" placeholder="Search..." onkeyup="loadReminders()">
            <div id="rem-list"></div>
        </div>

    </div>
</div>

<script>
let currentTab = "all";
let currentClient = 0;
let currentAssignee = "";
const me = <?= json_encode($csr_user) ?>;

// Toggle sidebar
function toggleSidebar(open) {
    const s = document.getElementById("sidebar");
    const o = document.getElementById("sidebar-overlay");
    if (open) {
        s.classList.add("active");
        o.style.display = "block";
    } else {
        s.classList.remove("active");
        o.style.display = "none";
    }
}

// Tab switching
function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    document.getElementById("tab-" + tab).classList.add("active");

    if (tab === "rem") {
        document.getElementById("messages").style.display = "none";
        document.getElementById("input").style.display = "none";
        document.getElementById("chat-head").style.display = "none";
        document.getElementById("reminders").style.display = "block";
        loadReminders();
    } else {
        document.getElementById("chat-head").style.display = "flex";
        document.getElementById("messages").style.display = "block";
        document.getElementById("input").style.display = "flex";
        document.getElementById("reminders").style.display = "none";
        loadClients();
    }
}

// Load clients
function loadClients() {
    fetch("csr_ajax.php?action=clients&tab=" + currentTab)
        .then(r => r.text())
        .then(html => {
            document.getElementById("client-col").innerHTML = html;
            document.querySelectorAll(".client-item").forEach(el => {
                el.addEventListener("click", () => selectClient(el));
            });
        });
}

// Avatar logic
function setAvatar(gender, avatar) {
    const box = document.getElementById("chatAvatar");
    box.innerHTML = "";

    let img = document.createElement("img");

    if (avatar) {
        img.src = avatar;
    } else if (gender === "female") {
        img.src = "../penguin.png";
    } else if (gender === "male") {
        img.src = "../lion.png";
    } else {
        img.src = "../penguin.png";
    }

    box.appendChild(img);
}

// Select client
function selectClient(el) {
    currentClient = el.dataset.id;
    currentAssignee = el.dataset.csr;
    const name = el.dataset.name;

    document.getElementById("chat-title-text").textContent = name;

    fetch("csr_ajax.php?action=client_profile&name=" + encodeURIComponent(name))
        .then(r => r.json())
        .then(data => {
            setAvatar(data.gender, data.avatar);
        });

    loadChat();
}

// Load chat messages
function loadChat() {
    if (!currentClient) return;
    fetch("csr_ajax.php?action=load_chat&client_id=" + currentClient)
        .then(r => r.json())
        .then(rows => {
            const box = document.getElementById("messages");
            box.innerHTML = "";

            rows.forEach(m => {
                box.innerHTML += `
                    <div class="msg ${m.sender_type}">
                        <div class="bubble">
                            <strong>${m.sender_name}:</strong> ${m.message}
                        </div>
                        <div class="time">${new Date(m.created_at).toLocaleString()}</div>
                    </div>
                `;
            });

            box.scrollTop = box.scrollHeight;
        });
}

// Send message
function sendMsg() {
    if (!currentClient) return;

    // Locked reply check
    if (currentAssignee !== "Unassigned" && currentAssignee !== me) {
        alert("This client is assigned to another CSR.");
        return;
    }

    const msg = document.getElementById("msg").value.trim();
    if (!msg) return;

    fetch("csr_ajax.php?action=send", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "client_id=" + currentClient + "&message=" + encodeURIComponent(msg)
    })
    .then(() => {
        document.getElementById("msg").value = "";
        loadChat();
    });
}

// Reminders
function loadReminders() {
    const q = document.getElementById("rem-q").value;
    fetch("csr_ajax.php?action=reminders&q=" + encodeURIComponent(q))
        .then(r => r.json())
        .then(list => {
            let html = "";
            list.forEach(item => {
                html += `<div class="card">
                    <b>${item.name}</b> (${item.email})<br>
                    Due: ${item.due}<br>
                </div>`;
            });
            document.getElementById("rem-list").innerHTML = html;
        });
}

loadClients();
setInterval(() => {
    if (currentClient) loadChat();
}, 5000);
</script>

</body>
</html>
