<?php
/**
 * CSR Dashboard — FULL FIXED VERSION
 * Fixed deprecated htmlspecialchars(null)
 * Added helper h()
 * Added auto-gender → lion/penguin
 * Repaired chat collapse button behavior
 */

session_start();
include '../db_connect.php';

// -----------------------------------------------------------------------------
// AUTH
// -----------------------------------------------------------------------------
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}
$csr_user = $_SESSION['csr_user'];

// -----------------------------------------------------------------------------
// SAFE ENCODING HELPER (fixes PHP 8.1+ deprecation)
// -----------------------------------------------------------------------------
function h($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// -----------------------------------------------------------------------------
// GET CSR NAME
// -----------------------------------------------------------------------------
$st = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username = :u LIMIT 1");
$st->execute([':u' => $csr_user]);
$csr = $st->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $csr['full_name'] ?? $csr_user;

// -----------------------------------------------------------------------------
// LOGO
// -----------------------------------------------------------------------------
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

// -----------------------------------------------------------------------------
// AJAX HANDLER
// -----------------------------------------------------------------------------
if (isset($_GET['ajax'])) {

    // -------------------------------------------------------------------------
    // CLIENT LIST
    // -------------------------------------------------------------------------
    if ($_GET['ajax'] === 'clients') {
        $tab = $_GET['tab'] ?? 'all';

        $sql = "
            SELECT c.id, c.name, c.assigned_csr,
            (SELECT email FROM users u WHERE u.full_name = c.name LIMIT 1) AS email,
            MAX(ch.created_at) AS last_chat
            FROM clients c
            LEFT JOIN chat ch ON ch.client_id = c.id
        ";

        $where = ($tab === 'mine') ? " WHERE c.assigned_csr = :csr " : "";
        $sql .= $where . " GROUP BY c.id, c.name, c.assigned_csr ORDER BY last_chat DESC NULLS LAST";

        $stc = $conn->prepare($sql);
        if ($tab === 'mine') {
            $stc->execute([':csr' => $csr_user]);
        } else {
            $stc->execute();
        }

        while ($row = $stc->fetch(PDO::FETCH_ASSOC)) {
            $assigned = $row['assigned_csr'] ?: 'Unassigned';
            $owned    = ($assigned === $csr_user);

            if ($assigned === 'Unassigned') {
                $btn = "<button class='pill green' onclick='assignClient({$row['id']})'>＋</button>";
            } elseif ($owned) {
                $btn = "<button class='pill red' onclick='unassignClient({$row['id']})'>−</button>";
            } else {
                $btn = "<button class='pill gray' disabled>🔒</button>";
            }

            echo "
                <div class='client-item' 
                     data-id='{$row['id']}'
                     data-name='".h($row['name'])."'
                     data-csr='".h($assigned)."'>
                     
                    <div class='client-meta'>
                        <div class='client-name'>".h($row['name'])."</div>
                        <div class='client-email'>".h($row['email'])."</div>
                        <div class='client-assign'>Assigned: ".h($assigned)."</div>
                    </div>
                    <div class='client-actions'>{$btn}</div>
                </div>
            ";
        }
        exit;
    }

    // -------------------------------------------------------------------------
    // LOAD CHAT MESSAGES
    // -------------------------------------------------------------------------
    if ($_GET['ajax'] === 'load_chat' && isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];

        $q = $conn->prepare("
            SELECT ch.message, ch.sender_type, ch.created_at, 
                   ch.assigned_csr, ch.csr_fullname, c.name AS client_name
            FROM chat ch 
            JOIN clients c ON c.id = ch.client_id
            WHERE ch.client_id = :cid
            ORDER BY ch.created_at ASC
        ");
        $q->execute([':cid' => $cid]);

        $rows = [];
        while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'message'      => $r['message'],
                'sender_type'  => $r['sender_type'],
                'time'         => date('Y-m-d H:i:s', strtotime($r['created_at'])),
                'client_name'  => $r['client_name'],
                'assigned_csr' => $r['assigned_csr'],
                'csr_fullname' => $r['csr_fullname']
            ];
        }

        echo json_encode($rows);
        exit;
    }

    // -------------------------------------------------------------------------
    // CLIENT PROFILE → avatar + gender
    // -------------------------------------------------------------------------
    if ($_GET['ajax'] === 'client_profile' && isset($_GET['name'])) {
        $name = trim($_GET['name']);

        $ps = $conn->prepare("SELECT email, gender FROM users WHERE full_name = :n LIMIT 1");
        $ps->execute([':n' => $name]);
        $u = $ps->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'email'  => $u['email']  ?? null,
            'gender' => $u['gender'] ?? null
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // ASSIGN
    // -------------------------------------------------------------------------
    if ($_GET['ajax'] === 'assign' && isset($_POST['client_id'])) {
        $id = (int)$_POST['client_id'];
        
        $chk = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
        $chk->execute([':id' => $id]);
        $cur = $chk->fetch(PDO::FETCH_ASSOC);

        if ($cur && $cur['assigned_csr'] && $cur['assigned_csr'] !== 'Unassigned') {
            echo "taken";
            exit;
        }
        $conn->prepare("UPDATE clients SET assigned_csr = :c WHERE id = :id")
             ->execute([':c' => $csr_user, ':id' => $id]);

        echo "ok";
        exit;
    }

    // -------------------------------------------------------------------------
    // UNASSIGN
    // -------------------------------------------------------------------------
    if ($_GET['ajax'] === 'unassign' && isset($_POST['client_id'])) {
        $id = (int)$_POST['client_id'];

        $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned' WHERE id = :id AND assigned_csr = :c")
             ->execute([':id' => $id, ':c' => $csr_user]);

        echo "ok";
        exit;
    }

    // -------------------------------------------------------------------------
    // REMINDERS LIST
    // -------------------------------------------------------------------------
    if ($_GET['ajax'] === 'reminders') {
        $search = strtolower(trim($_GET['q'] ?? ''));

        $rows = [];
        $today = new DateTime('today');

        $usrQ = $conn->query("SELECT id, full_name, email, date_installed FROM users ORDER BY full_name ASC")
                    ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($usrQ as $u) {
            if (!$u['date_installed'])
                continue;

            $install = new DateTime($u['date_installed']);

            $base = new DateTime('first day of this month');
            $due_day = (int)$install->format('d');

            $due = (clone $base)->setDate(
                (int)$base->format('Y'),
                (int)$base->format('m'),
                min($due_day, 28)
            );

            if ((int)$today->format('d') > (int)$due->format('d')) {
                $base->modify('first day of next month');
                $due = (clone $base)->setDate(
                    (int)$base->format('Y'),
                    (int)$base->format('m'),
                    min($due_day, 28)
                );
            }

            $oneWeek  = (clone $due)->modify('-7 days');
            $threeDay = (clone $due)->modify('-3 days');

            $st = $conn->prepare("SELECT reminder_type, status FROM reminders WHERE client_id = :id AND cycle_date = :cy");
            $st->execute([':id'=>$u['id'], ':cy'=>$due->format('Y-m-d')]);

            $sentMap = [];
            foreach ($st as $s) {
                $sentMap[$s['reminder_type']] = $s['status'];
            }

            $bad = [];

            if ($today <= $oneWeek && $today->diff($oneWeek)->days <= 7) {
                $bad[] = [
                    'type'=>'1_WEEK',
                    'status'=>($sentMap['1_WEEK']??'')==='sent'?'sent':'upcoming',
                    'date'=>$oneWeek->format('Y-m-d')
                ];
            }
            if ($today <= $threeDay && $today->diff($threeDay)->days <= 7) {
                $bad[] = [
                    'type'=>'3_DAYS',
                    'status'=>($sentMap['3_DAYS']??'')==='sent'?'sent':'upcoming',
                    'date'=>$threeDay->format('Y-m-d')
                ];
            }

            if (!$bad)
                continue;

            if ($search) {
                $hay = strtolower($u['full_name'].' '.$u['email']);
                if (strpos($hay, $search) === false)
                    continue;
            }

            $rows[] = [
                'user_id' => $u['id'],
                'name'    => $u['full_name'],
                'email'   => $u['email'],
                'due'     => $due->format('Y-m-d'),
                'banners' => $bad
            ];
        }

        echo json_encode($rows);
        exit;
    }

    echo "bad";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CSR Dashboard — <?= $csr_fullname ?></title>
<style>
body {
    margin:0; font-family:Segoe UI,Arial; background:#f7fff8; overflow:hidden;
}
header {
    height:60px; background:#0aa05b; color:white; display:flex; align-items:center;
    justify-content:space-between; padding:0 18px; font-weight:700;
}
#hamb { font-size:26px; background:none; border:none; color:white; cursor:pointer; }
#sidebar {
    position:fixed; top:0; left:-250px; width:250px; height:100vh;
    background:#068c49; color:white; transition:0.25s; z-index:1000; padding-top:20px;
}
#sidebar.active { left:0; }
#sidebar a {
    display:block; padding:14px 20px; text-decoration:none; color:white; font-weight:bold;
}
#sidebar a:hover { background:#0fbb6f; }

.tabs {
    display:flex; gap:10px; background:#eafdf0; padding:10px; border-bottom:1px solid #cfe9d8;
}
.tab {
    padding:8px 16px; border-radius:20px; border:1px solid #b5ddc5;
    background:white; cursor:pointer; font-weight:bold;
}
.tab.active { background:#0aa05b; color:white; border-color:#0aa05b; }

#main {
    height:calc(100vh - 110px);
    display:grid; grid-template-columns:330px 1fr;
}

#client-col { overflow:auto; background:white; border-right:1px solid #cfe9d8; }

.client-item {
    padding:12px; margin:10px; border:1px solid #e0eee4; border-radius:12px;
    background:white; cursor:pointer; display:flex; justify-content:space-between;
    align-items:center;
}
.client-item:hover { background:#f3fff8; }

.client-name { font-weight:bold; }
.client-email { font-size:12px; color:#02834a; }
.pill { border:none; padding:4px 10px; border-radius:20px; color:white; cursor:pointer; }
.pill.green { background:#15b66f; }
.pill.red { background:#d15252; }
.pill.gray { background:#8894a2; cursor:not-allowed; }

#chat-col { position:relative; display:flex; flex-direction:column; }

#collapseBtn {
    position:absolute; top:14px; right:14px; width:36px; height:36px;
    border-radius:50%; border:1px solid #cce0d5; display:flex; align-items:center;
    justify-content:center; cursor:pointer; background:white; font-size:18px;
}

#chat-col.collapsed #messages,
#chat-col.collapsed #input {
    display:none;
}
#chat-col.collapsed #chat-head {
    display:none;
}

#chat-col.collapsed #collapseBtn::after {
    content:"i"; font-weight:bold; color:#0aa05b;
}

#chat-head {
    background:#0aa05b; color:white; padding:12px; display:flex;
    justify-content:space-between; align-items:center;
}

#messages {
    flex:1; overflow:auto; padding:20px; position:relative;
}

.msg { max-width:70%; margin-bottom:12px; }
.msg.client { float:left; }
.msg.csr { float:right; }
.msg .bubble {
    padding:12px 16px; border-radius:16px; background:#ecfff2; position:relative;
}
.msg.csr .bubble { background:#e2f0ff; }
.msg .meta { font-size:11px; color:#556; margin-top:5px; }

#input {
    padding:10px; display:flex; gap:10px; border-top:1px solid #d8e7dd;
}
#input input {
    flex:1; padding:10px; border:1px solid #ccdcd0; border-radius:10px;
}
#input button {
    padding:10px 18px; background:#0aa05b; color:white; border:none;
    border-radius:10px; font-weight:bold; cursor:pointer;
}

.avatar {
    width:32px; height:32px; border-radius:50%; overflow:hidden;
    background:white; border:2px solid rgba(255,255,255,0.6);
    display:flex; justify-content:center; align-items:center; color:#0aa05b; font-weight:bold;
}
.avatar img { width:100%; height:100%; object-fit:cover; }
</style>
</head>
<body>

<header>
    <button id="hamb" onclick="toggleSidebar()">☰</button>
    <div>CSR Dashboard — <?= $csr_fullname ?></div>
</header>

<div id="sidebar">
    <a onclick="switchTab('all')">💬 All Clients</a>
    <a onclick="switchTab('mine')">👤 My Clients</a>
    <a onclick="switchTab('rem')">⏰ Reminders</a>
    <a href="survey_responses.php">📝 Survey Responses</a>
    <a href="update_profile.php">👤 Edit Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<div class="tabs">
    <div id="tab-all"  class="tab active" onclick="switchTab('all')">💬 All Clients</div>
    <div id="tab-mine" class="tab" onclick="switchTab('mine')">👤 My Clients</div>
    <div id="tab-rem"  class="tab" onclick="switchTab('rem')">⏰ Reminders</div>
</div>

<div id="main">
    <div id="client-col"></div>

    <div id="chat-col">
        <div id="collapseBtn" onclick="toggleChat()">…</div>

        <div id="chat-head">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar" id="chatAvatar"></div>
                <span id="chat-title">Select a client</span>
            </div>
        </div>

        <div id="messages"></div>

        <div id="input" style="display:none;">
            <input id="msg" placeholder="Type here...">
            <button onclick="sendMsg()">Send</button>
        </div>

    </div>
</div>

<script>
let currentClient = 0;
let currentAssignee = "";
let me = <?= json_encode((string)$csr_user) ?>;
let chatCollapsed = false;

function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    sb.classList.toggle('active');
}

function toggleChat() {
    const col = document.getElementById('chat-col');
    chatCollapsed = !chatCollapsed;

    if (chatCollapsed) {
        col.classList.add('collapsed');
    } else {
        col.classList.remove('collapsed');
    }
}

function switchTab(which) {
    document.getElementById('tab-all').classList.remove('active');
    document.getElementById('tab-mine').classList.remove('active');
    document.getElementById('tab-rem').classList.remove('active');

    document.getElementById('tab-' + which).classList.add('active');

    if (which === 'rem') {
        return;
    }
    loadClients(which);
}

function loadClients(tab) {
    fetch("csr_dashboard.php?ajax=clients&tab=" + tab)
        .then(r => r.text())
        .then(html => {
            document.getElementById('client-col').innerHTML = html;
            document.querySelectorAll('.client-item').forEach(el => {
                el.addEventListener('click', () => selectClient(el));
            });
        });
}

function selectClient(el) {
    currentClient = el.dataset.id;
    currentAssignee = el.dataset.csr;
    const name = el.dataset.name;

    document.getElementById('chat-title').textContent = name;
    loadAvatar(name);

    document.getElementById('input').style.display = (currentAssignee === 'Unassigned' || currentAssignee === me) ? 'flex' : 'none';

    loadChat();
}
const penguinIcon = "penguin.png";
const lionIcon    = "../lion.png";

function nameGuessGender(name) {
    const n = name.toLowerCase();
    // simple guess
    if (n.includes("macy") || n.includes("alec") || n.endsWith("a"))
        return "female";
    return "male";
}

function setAvatar(name, gender) {
    const div = document.getElementById("chatAvatar");
    div.innerHTML = "";

    if (!gender) {
        gender = nameGuessGender(name);
    }

    const img = document.createElement("img");
    img.src = (gender === "female") ? penguinIcon : lionIcon;
    div.appendChild(img);
}

// collapse toggle changes (…) → (i)
function toggleRight() {
    const btn = document.getElementById("collapseBtn");
    const col = document.getElementById("chat-col");

    if (col.classList.contains("collapsed")) {
        col.classList.remove("collapsed");
        btn.textContent = "…";
    } else {
        col.classList.add("collapsed");
        btn.textContent = "i";
    }
}

function loadAvatar(name) {
    fetch("csr_dashboard.php?ajax=client_profile&name="+encodeURIComponent(name))
        .then(r=>r.json())
        .then(data=>{
            const slot = document.getElementById('chatAvatar');
            slot.innerHTML = "";
            if (data.gender === "female") {
                const img = document.createElement("img");
                img.src = "https://i.imgur.com/1vQfPBk.png"; // chibi penguin
                slot.appendChild(img);
            } else if (data.gender === "male") {
                const img = document.createElement("img");
                img.src = "https://i.imgur.com/VhQ9mFq.png"; // chibi lion
                slot.appendChild(img);
            } else {
                slot.textContent = name.split(" ").map(n=>n[0]).join("");
            }
        });
}

function loadChat() {
    fetch("csr_dashboard.php?ajax=load_chat&client_id="+currentClient)
        .then(r=>r.json())
        .then(list=>{
            const box = document.getElementById('messages');
            box.innerHTML = "";
            list.forEach(m=>{
                const who = (m.sender_type === "csr") ? "csr" : "client";
                const name = (who === "csr") ? (m.csr_fullname || "CSR") : m.client_name;
                box.innerHTML += `
                <div class="msg ${who}">
                    <div class="bubble"><strong>${name}:</strong> ${m.message}</div>
                    <div class="meta">${new Date(m.time).toLocaleString()}</div>
                </div>`;
            });
            box.scrollTop = box.scrollHeight;
        });
}

function sendMsg() {
    if (!currentClient) return;
    if (currentAssignee !== "Unassigned" && currentAssignee !== me) {
        alert("This client is assigned to another CSR.");
        return;
    }

    const msg = document.getElementById('msg').value.trim();
    if (!msg) return;

    const body = new URLSearchParams();
    body.append("sender_type", "csr");
    body.append("message", msg);
    body.append("client_id", currentClient);

    fetch("../SKYTRUFIBER/save_chat.php", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:body.toString()
    }).then(()=>{
        document.getElementById('msg').value = "";
        loadChat();
    });
}

function assignClient(id) {
    fetch("csr_dashboard.php?ajax=assign", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"client_id="+id
    }).then(r=>r.text()).then(t=>{
        if (t==="ok") loadClients("all");
        if (t==="taken") alert("Already assigned to another CSR.");
    });
}

function unassignClient(id) {
    if (!confirm("Unassign client?")) return;
    fetch("csr_dashboard.php?ajax=unassign", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"client_id="+id
    }).then(()=>loadClients("all"));
}

loadClients("all");

setInterval(()=>{
    if (currentClient) loadChat();
},5000);
</script>

</body>
</html>
