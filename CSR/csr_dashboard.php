<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Get CSR details
$stmt = $conn->prepare("SELECT full_name, profile_pic FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$csr_fullname = $user['full_name'] ?? $csr_user;
$csr_avatar = $user['profile_pic'] ?? "CSR/default_avatar.png";

/* ===================================================
   AJAX HANDLERS
=================================================== */

if (isset($_GET['ajax'])) {
    header("Content-Type: application/json");

    /* -------------------------
       LOAD CLIENTS
    -------------------------- */
    if ($_GET['ajax'] === "load_clients") {
        $tab = $_GET['tab'] ?? "all";

        if ($tab === "mine") {
            $stmt = $conn->prepare("SELECT * FROM clients WHERE assigned_csr = :csr ORDER BY name ASC");
            $stmt->execute([':csr' => $csr_user]);
        } else {
            $stmt = $conn->query("SELECT * FROM clients ORDER BY name ASC");
        }

        $clients = [];
        while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $clients[] = [
                'id' => $c['id'],
                'name' => $c['name'],
                'email' => $c['email_address'] ?? '',
                'assigned_csr' => $c['assigned_csr'],
                'status' => (strtotime($c['last_active']) > time() - 60) ? "Online" : "Offline"
            ];
        }

        echo json_encode($clients);
        exit;
    }

    /* -------------------------
       GET CLIENT INFO
    -------------------------- */
    if ($_GET['ajax'] === "get_client_info" && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("
            SELECT name, email_address, district, barangay, date_installed, balance, assigned_csr
            FROM clients WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        exit;
    }

    /* -------------------------
       ASSIGN CLIENT (Manual)
    -------------------------- */
    if ($_GET['ajax'] === "assign_client" && isset($_GET['id'])) {
        $id = intval($_GET['id']);

        // Only assign if NULL
        $stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id AND assigned_csr IS NULL");
        $stmt->execute([':csr' => $csr_user, ':id' => $id]);

        echo json_encode(['success' => true]);
        exit;
    }

    /* -------------------------
       UNASSIGN CLIENT
    -------------------------- */
    if ($_GET['ajax'] === "unassign_client" && isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :id AND assigned_csr = :csr");
        $stmt->execute([':id' => $id, ':csr' => $csr_user]);

        echo json_encode(['success' => true]);
        exit;
    }

    /* -------------------------
       LOAD CHAT
    -------------------------- */
    if ($_GET['ajax'] === "load_chat" && isset($_GET['client_id'])) {
        $cid = intval($_GET['client_id']);

        $stmt = $conn->prepare("
            SELECT ch.*, c.name AS client_name
            FROM chat ch
            JOIN clients c ON c.id = ch.client_id
            WHERE ch.client_id = :cid
            ORDER BY ch.created_at ASC
        ");
        $stmt->execute([':cid' => $cid]);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /* -------------------------
       SEND MESSAGE
    -------------------------- */
    if ($_GET['ajax'] === "send_msg" && $_SERVER['REQUEST_METHOD'] === "POST") {
        $cid = intval($_POST['client_id']);
        $msg = trim($_POST['msg']);

        if ($msg !== "") {
            $stmt = $conn->prepare("
                INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at)
                VALUES (:cid, 'csr', :msg, :csr, NOW())
            ");
            $stmt->execute([
                ':cid' => $cid,
                ':msg' => $msg,
                ':csr' => $csr_fullname
            ]);
        }

        echo json_encode(['ok' => true]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= $csr_fullname ?></title>
<link rel="stylesheet" href="csr_dashboard.css?v=10">

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<!-- ========== OVERLAY ========== -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- ========== SIDEBAR ========== -->
<div class="sidebar" id="sidebar">
    <button onclick="switchTab('dashboard')" class="side-btn"><i class="fa-solid fa-comments"></i> Chat Dashboard</button>
    <button onclick="switchTab('mine')" class="side-btn"><i class="fa-solid fa-user"></i> My Clients</button>
    <button onclick="window.location='reminders.php'" class="side-btn"><i class="fa-solid fa-clock"></i> Reminders</button>
    <button onclick="window.location='survey_responses.php'" class="side-btn"><i class="fa-solid fa-clipboard-list"></i> Survey Responses</button>
    <button onclick="window.location='update_profile.php'" class="side-btn"><i class="fa-solid fa-user-gear"></i> Edit Profile</button>
    <button onclick="window.location='csr_logout.php'" class="side-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button>
</div>

<!-- ========== TOP BAR ========== -->
<header class="topbar">
    <button class="hamburger-btn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
    <div class="left">
        <img src="AHBALOGO.png" class="logo">
        <h1>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></h1>
    </div>
    <a href="csr_logout.php" class="logout"><i class="fa-solid fa-door-open"></i> Logout</a>
</header>

<!-- ========== TOP TABS ========== -->
<div class="top-tabs">
    <div class="top-tab active"><i class="fa-solid fa-comments"></i> Chat Dashboard</div>
    <div class="top-tab"><i class="fa-solid fa-user"></i> My Clients</div>
    <div class="top-tab"><i class="fa-solid fa-clock"></i> Reminders</div>
    <div class="top-tab"><i class="fa-solid fa-clipboard-list"></i> Survey Responses</div>
    <div class="top-tab"><i class="fa-solid fa-user-gear"></i> Edit Profile</div>
</div>

<!-- ========== MAIN LAYOUT ========== -->
<div class="dashboard-wrap">

    <!-- ===== LEFT CLIENT PANEL ===== -->
    <div class="left-panel">
        <div class="client-title-box">
            <i class="fa-solid fa-users"></i> CLIENTS
        </div>

        <input type="text" placeholder="Search client…" class="client-search">

        <div class="client-list" id="clientList"></div>
    </div>

    <!-- ===== RIGHT CHAT PANEL ===== -->
    <div class="right-panel">

        <div class="chat-header">
            <div class="chat-title" id="clientName">Select a client</div>
        </div>

        <div class="messages" id="messages">
            <p class="placeholder">Select a client to start chatting.</p>
        </div>

        <div class="input-box">
            <input type="text" id="msg" placeholder="Type message…" onkeyup="checkEnter(event)">
            <button onclick="sendMsg()"><i class="fa-solid fa-paper-plane"></i></button>
        </div>

        <!-- ===== RIGHT COLLAPSIBLE INFO ===== -->
        <div class="client-info" id="clientInfo"></div>

    </div>

</div>


<script>
let currentClient = null;
let canChat = false;

/* ============================
   SIDEBAR
============================ */
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("open");
    document.getElementById("overlay").classList.toggle("show");
}

/* ============================
   LOAD CLIENT LIST
============================ */
async function loadClients(tab="all"){
    const res = await fetch(`?ajax=load_clients&tab=${tab}`);
    const list = await res.json();

    const box = document.getElementById("clientList");
    box.innerHTML = "";

    list.forEach(c=>{
        const locked = (c.assigned_csr && c.assigned_csr !== "<?= $csr_user ?>");
        const icon = locked ? `<i class='fa-solid fa-lock'></i>` : `<i class='fa-solid fa-circle-plus'></i>`;

        box.innerHTML += `
            <div class="client-item" onclick="openInfo(${c.id})">
                <div class="client-name">${icon} ${c.name}</div>
                <div class="client-email">${c.email}</div>
            </div>
        `;
    });
}

/* ============================
   OPEN RIGHT CLIENT INFO
============================ */
async function openInfo(id){
    const panel = document.getElementById("clientInfo");

    const res = await fetch(`?ajax=get_client_info&id=${id}`);
    const c = await res.json();

    const owner = (c.assigned_csr === "<?= $csr_user ?>");
    const unassigned = (c.assigned_csr === null);

    panel.innerHTML = `
        <h3>Client Information</h3>
        <div class="info-row"><b>Name:</b> ${c.name}</div>
        <div class="info-row"><b>Email:</b> ${c.email_address}</div>
        <div class="info-row"><b>District:</b> ${c.district}</div>
        <div class="info-row"><b>Barangay:</b> ${c.barangay}</div>
        <div class="info-row"><b>Installed:</b> ${c.date_installed}</div>
        <div class="info-row"><b>Balance:</b> ₱${c.balance ?? '0.00'}</div>
        <div class="info-row"><b>Assigned CSR:</b> ${c.assigned_csr ?? "None"}</div>

        ${unassigned ? `
            <button onclick="assignClient(${id})" class="assign-btn assign">Assign to Me</button>
        ` : owner ? `
            <button onclick="unassignClient(${id})" class="assign-btn unassign">Unassign</button>
        ` : `
            <button class="assign-btn" style="background:#999;cursor:not-allowed;">Locked</button>
        `}

        <button onclick="openChat(${id}, '${c.name}', ${owner})" class="assign-btn" style="background:#0a7f46;color:white;margin-top:15px;">
            Open Chat
        </button>
    `;

    panel.classList.add("open");
}

/* ============================
   ASSIGN / UNASSIGN
============================ */
async function assignClient(id){
    await fetch(`?ajax=assign_client&id=${id}`);
    loadClients();
    openInfo(id);
}

async function unassignClient(id){
    await fetch(`?ajax=unassign_client&id=${id}`);
    loadClients();
    openInfo(id);
}

/* ============================
   OPEN CHAT
============================ */
function openChat(id, name, owner){
    currentClient = id;
    canChat = owner;

    document.getElementById("clientName").innerHTML = name;

    loadChat();

    setInterval(loadChat, 4000);
}

/* ============================
   LOAD CHAT MESSAGES
============================ */
async function loadChat(){
    if (!currentClient) return;

    const res = await fetch(`?ajax=load_chat&client_id=${currentClient}`);
    const messages = await res.json();
    const box = document.getElementById("messages");

    box.innerHTML = "";

    messages.forEach(m=>{
        box.innerHTML += `
            <div class="message ${m.sender_type}">
                <div class="bubble">
                    <b>${m.sender_type === "csr" ? "You" : m.client_name}:</b> ${m.message}
                    <div class="meta">${m.created_at}</div>
                </div>
            </div>
        `;
    });

    box.scrollTop = box.scrollHeight;
}

/* ============================
   SEND MESSAGE
============================ */
async function sendMsg(){
    if (!canChat) {
        alert("You cannot chat with a client assigned to another CSR.");
        return;
    }

    const msg = document.getElementById("msg").value.trim();
    if (msg === "") return;

    await fetch("?ajax=send_msg",{
        method:"POST",
        body:new URLSearchParams({
            client_id: currentClient,
            msg
        })
    });

    document.getElementById("msg").value = "";
    loadChat();
}

function checkEnter(e){
    if (e.key === "Enter") sendMsg();
}

loadClients();
</script>

</body>
</html>