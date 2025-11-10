<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}
$csr_user = $_SESSION['csr_user'];

$st = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username = :u LIMIT 1");
$st->execute([':u' => $csr_user]);
$csr = $st->fetch(PDO::FETCH_ASSOC);

$csr_fullname = isset($csr['full_name']) ? htmlspecialchars((string)$csr['full_name']) : htmlspecialchars((string)$csr_user);

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

function guessGenderFromName($name) {
    $femaleNames = ['macy','alice','ella','mary','sophia','grace','emma','ava','mia','isabella','amelia','olivia','arianna','ari','alyssa'];
    $maleNames   = ['boss','aaron','jon','john','paul','mark','michael','waldo','adam','jay','leo','liam','daniel','alex'];
    $fn = strtolower($name);
    foreach ($femaleNames as $f) {
        if (strpos($fn, $f) !== false) return 'female';
    }
    foreach ($maleNames as $m) {
        if (strpos($fn, $m) !== false) return 'male';
    }
    return null;
}

if (isset($_GET['ajax'])) {

    if ($_GET['ajax'] === 'clients') {
        $tab = $_GET['tab'] ?? 'all';
        $sql = "
            SELECT c.id, c.name, c.assigned_csr,
                   (SELECT email FROM users u WHERE u.full_name = c.name LIMIT 1) AS email,
                   MAX(ch.created_at) AS last_chat
            FROM clients c
            LEFT JOIN chat ch ON ch.client_id = c.id
        ";

        $params = [];
        if ($tab === 'mine') {
            $sql .= " WHERE c.assigned_csr = :csr ";
            $params[':csr'] = $csr_user;
        }

        $sql .= " GROUP BY c.id, c.name, c.assigned_csr 
                  ORDER BY last_chat DESC NULLS LAST";

        $stc = $conn->prepare($sql);
        $stc->execute($params);

        while ($row = $stc->fetch(PDO::FETCH_ASSOC)) {
            $name = htmlspecialchars((string)$row['name']);
            $email = htmlspecialchars((string)($row['email'] ?? ''));

            $assigned = $row['assigned_csr'] ?? 'Unassigned';
            $owned = ($assigned === $csr_user);

            if ($assigned === 'Unassigned') {
                $btn = "<button class='pill green' onclick='assignClient({$row['id']})'>＋</button>";
            } elseif ($owned) {
                $btn = "<button class='pill red' onclick='unassignClient({$row['id']})'>−</button>";
            } else {
                $btn = "<button class='pill gray' disabled>🔒</button>";
            }

            echo "
            <div class='client-item' data-id='{$row['id']}' data-name=\"".htmlspecialchars((string)$row['name'],ENT_QUOTES)."\" data-csr=\"".htmlspecialchars((string)$assigned,ENT_QUOTES)."\">
                <div>
                    <div class='client-name'>{$name}</div>
                    <div class='client-email'>{$email}</div>
                    <div class='client-assign'>Assigned: {$assigned}</div>
                </div>
                <div>{$btn}</div>
            </div>";
        }
        exit;
    }

    if ($_GET['ajax'] === 'client_profile' && isset($_GET['name'])) {
        $name = $_GET['name'];
        $ps = $conn->prepare("SELECT email FROM users WHERE full_name = :n LIMIT 1");
        $ps->execute([':n' => $name]);
        $usr = $ps->fetch(PDO::FETCH_ASSOC);
        $gender = guessGenderFromName($name);
        echo json_encode([
            'email' => $usr['email'] ?? null,
            'gender' => $gender
        ]);
        exit;
    }

    if ($_GET['ajax'] === 'load_chat' && isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];
        $q = $conn->prepare("
            SELECT ch.message, ch.sender_type, ch.created_at, ch.assigned_csr, ch.csr_fullname, c.name AS client_name
            FROM chat ch
            JOIN clients c ON c.id = ch.client_id
            WHERE ch.client_id = :cid
            ORDER BY ch.created_at ASC
        ");
        $q->execute([':cid' => $cid]);

        $rows = [];
        while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'message' => $r['message'],
                'sender_type' => $r['sender_type'],
                'time' => date("Y-m-d H:i:s", strtotime($r['created_at'])),
                'client_name' => $r['client_name'],
                'assigned_csr' => $r['assigned_csr'],
                'csr_fullname' => $r['csr_fullname']
            ];
        }
        echo json_encode($rows);
        exit;
    }

    if ($_GET['ajax'] === 'assign' && isset($_POST['client_id'])) {
        $id = (int)$_POST['client_id'];
        $check = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
        $check->execute([':id' => $id]);
        $cur = $check->fetch(PDO::FETCH_ASSOC);
        if (!empty($cur['assigned_csr']) && $cur['assigned_csr'] !== 'Unassigned') {
            echo "taken"; exit;
        }
        $upd = $conn->prepare("UPDATE clients SET assigned_csr = :c WHERE id = :id");
        $ok = $upd->execute([':c' => $csr_user, ':id' => $id]);
        echo $ok ? "ok" : "fail"; exit;
    }

    if ($_GET['ajax'] === 'unassign' && isset($_POST['client_id'])) {
        $id = (int)$_POST['client_id'];
        $upd = $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned' WHERE id = :id AND assigned_csr = :c");
        $ok = $upd->execute([':id'=>$id, ':c'=>$csr_user]);
        echo $ok ? "ok" : "fail"; exit;
    }

    if ($_GET['ajax'] === 'reminders') {
        echo json_encode([]); exit;
    }

    http_response_code(400);
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
