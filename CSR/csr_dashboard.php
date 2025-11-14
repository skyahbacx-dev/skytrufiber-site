<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Get CSR Info
$stmt = $conn->prepare("SELECT full_name, profile_pic FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$csr_fullname = $row['full_name'] ?? $csr_user;
$csr_avatar   = $row['profile_pic'] ?? 'CSR/default_avatar.png';


/* ===================================================
    AJAX ROUTES
=================================================== */

if (isset($_GET['ajax'])) {

    header("Content-Type: application/json");

    /* Load Clients */
    if ($_GET['ajax'] === "load_clients") {
        $tab = $_GET['tab'] ?? "all";

        if ($tab === "mine") {
            $stmt = $conn->prepare("SELECT * FROM clients WHERE assigned_csr = :csr ORDER BY name ASC");
            $stmt->execute(['csr' => $csr_user]);
        } else {
            $stmt = $conn->query("SELECT * FROM clients ORDER BY name ASC");
        }

        $out = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'email' => $r['email'],
                'status' => strtotime($r['last_active']) > time() - 60 ? "Online" : "Offline",
                'assigned_csr' => $r['assigned_csr']
            ];
        }
        echo json_encode($out);
        exit;
    }


    /* Get Client Info */
    if ($_GET['ajax'] === "get_client_info") {
        $id = (int)$_GET['id'];

        $stmt = $conn->prepare("
            SELECT id, name, email, district, barangay, balance, date_installed, assigned_csr
            FROM clients WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);

        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit;
    }


    /* Assign Client */
    if ($_GET['ajax'] === "assign_client") {
        $id = (int)$_GET['id'];

        $stmt = $conn->prepare("
            UPDATE clients SET assigned_csr = :csr 
            WHERE id = :id AND assigned_csr IS NULL
        ");
        $stmt->execute(['csr' => $csr_user, 'id' => $id]);

        echo json_encode(['success' => true]);
        exit;
    }


    /* Unassign */
    if ($_GET['ajax'] === "unassign_client") {
        $id = (int)$_GET['id'];

        $stmt = $conn->prepare("
            UPDATE clients SET assigned_csr = NULL 
            WHERE id = :id AND assigned_csr = :csr
        ");
        $stmt->execute(['csr' => $csr_user, 'id' => $id]);

        echo json_encode(['success' => true]);
        exit;
    }


    /* Load Chat */
    if ($_GET['ajax'] === "load_chat") {
        $cid = (int)$_GET['client_id'];

        $stmt = $conn->prepare("
            SELECT c.name AS client, ch.*
            FROM chat ch 
            JOIN clients c ON ch.client_id = c.id
            WHERE ch.client_id = :cid
            ORDER BY ch.created_at ASC
        ");
        $stmt->execute(['cid' => $cid]);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }


    /* Send Message */
    if ($_GET['ajax'] === "send_msg" && $_SERVER['REQUEST_METHOD'] === "POST") {
        $cid = (int)$_POST['client_id'];
        $msg = trim($_POST['msg']);

        if ($msg !== "") {
            $stmt = $conn->prepare("
                INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
                VALUES (:cid, 'csr', :msg, :csr, NOW())
            ");
            $stmt->execute([
                'cid' => $cid,
                'msg' => $msg,
                'csr' => $csr_fullname
            ]);
        }

        echo json_encode(['ok' => true]);
        exit;
    }

    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></title>
    <link rel="stylesheet" href="csr_dashboard.css?v=10">
</head>

<body>

<!-- ========================= TOP BAR ========================= -->
<header class="topbar">
    <button id="openSidebar" class="menu-btn">☰</button>

    <div class="logo-area">
        <img src="AHBALOGO.png" class="logo">
        <h1>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></h1>
    </div>

    <a href="csr_logout.php" class="logout">Logout</a>
</header>


<!-- ========================= TABS ========================= -->
<nav class="tabs">
    <button onclick="go('csr_dashboard.php')" class="active">💬 Chat Dashboard</button>
    <button onclick="go('csr_dashboard.php?tab=mine')">👤 My Clients</button>
    <button onclick="go('reminders.php')">⏰ Reminders</button>
    <button onclick="go('survey_responses.php')">📄 Survey Responses</button>
    <button onclick="go('update_profile.php')">👤 Edit Profile</button>
</nav>


<!-- ========================= SIDEBAR (HIDDEN BY DEFAULT) ========================= -->
<aside id="sidebar" class="sidebar">
    <button id="closeSidebar" class="close">✕</button>

    <h3 class="side-title">CLIENTS</h3>

    <input id="clientSearch" class="search" placeholder="Search client…">

    <div id="clientList"></div>
</aside>

<div id="overlay"></div>


<!-- ========================= MAIN AREA ========================= -->
<div class="main">

    <div class="chat-container">

        <div class="chat-header">
            <span id="clientName">Select a client</span>
        </div>

        <div class="messages" id="messages">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <div id="inputArea" class="input-area locked">
            <input id="msg" placeholder="type anything….." onkeyup="typing(event)">
            <button onclick="sendMsg()">➤</button>
        </div>
    </div>

    <div id="clientInfo" class="client-info collapsed"></div>

</div>




<!-- ========================= JAVASCRIPT ========================= -->
<script>
/* Navigation */
function go(url) { window.location.href = url; }

/* Sidebar Controls */
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");

document.getElementById("openSidebar").onclick = () => {
    sidebar.classList.add("open");
    overlay.style.display = "block";
};

document.getElementById("closeSidebar").onclick = closeSidebar;
overlay.onclick = closeSidebar;

function closeSidebar() {
    sidebar.classList.remove("open");
    overlay.style.display = "none";
}


/* Load Clients */
async function loadClients() {
    const res = await fetch("?ajax=load_clients");
    const clients = await res.json();

    let html = "";
    clients.forEach(c => {
        const locked = c.assigned_csr && c.assigned_csr !== "<?= $csr_user ?>";
        const icon = locked ? "🔒" : (c.assigned_csr ? "➖" : "➕");

        html += `
            <div class="client-item ${locked ? "locked" : ""}" onclick="selectClient(${c.id}, '${c.name}', ${locked})">
                <div class="icon">${icon}</div>
                <div class="info">
                    <b>${c.name}</b><br>
                    <span>${c.email}</span>
                </div>
            </div>
        `;
    });

    document.getElementById("clientList").innerHTML = html;
}

loadClients();


/* Select client + load chat */
let currentID = null;
let canChat = false;

async function selectClient(id, name, locked) {
    currentID = id;
    canChat = !locked;

    document.getElementById("clientName").innerText = name;

    // Load chat
    loadChat();

    // Show info panel
    loadClientInfo(id);

    // Enable or disable chat input
    document.getElementById("inputArea").classList.toggle("locked", locked);
}


/* Load client info */
async function loadClientInfo(id) {
    const res = await fetch("?ajax=get_client_info&id=" + id);
    const c = await res.json();

    document.getElementById("clientInfo").innerHTML = `
        <h3>Client Information</h3>
        <b>${c.name}</b><br>
        ${c.email}<br><br>
        District: ${c.district}<br>
        Barangay: ${c.barangay}<br>
        Balance: ₱${c.balance}<br>
        Date Installed: ${c.date_installed}<br><br>

        ${
            c.assigned_csr === null ?
            `<button onclick="assign(${c.id})" class="assign-btn">Assign to Me</button>` :
            c.assigned_csr === "<?= $csr_user ?>" ?
            `<button onclick="unassign(${c.id})" class="assign-btn unassign">Unassign</button>` :
            `<p style='color:red;'>Assigned to ${c.assigned_csr}</p>`
        }
    `;

    document.getElementById("clientInfo").classList.remove("collapsed");
}


/* Assign / Unassign */
async function assign(id) {
    await fetch("?ajax=assign_client&id=" + id);
    loadClients();
    loadClientInfo(id);
}

async function unassign(id) {
    await fetch("?ajax=unassign_client&id=" + id);
    loadClients();
    loadClientInfo(id);
}


/* Load Chat */
async function loadChat() {
    if (!currentID) return;

    const res = await fetch("?ajax=load_chat&client_id=" + currentID);
    const data = await res.json();

    const m = document.getElementById("messages");
    m.innerHTML = "";

    data.forEach(msg => {
        const mine = msg.sender_type === "csr";

        m.innerHTML += `
            <div class="message ${mine ? "mine" : "theirs"}">
                <div class="bubble">${msg.message}</div>
            </div>
        `;
    });

    m.scrollTop = m.scrollHeight;
}


/* Send Message */
async function sendMsg() {
    if (!canChat || !currentID) return;

    const msg = document.getElementById("msg").value.trim();
    if (!msg) return;

    await fetch("?ajax=send_msg", {
        method: "POST",
        body: new URLSearchParams({
            client_id: currentID,
            msg: msg
        })
    });

    document.getElementById("msg").value = "";

    loadChat();
}

function typing(e) {
    if (e.key === "Enter") sendMsg();
}
</script>

</body>
</html>
