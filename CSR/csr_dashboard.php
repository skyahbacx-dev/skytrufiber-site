<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Load CSR details
$stmt = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$csr = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $csr['full_name'] ?? $csr_user;

// Logo
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

// ----------------------------------------------------------------------------
// AJAX HANDLERS
// ----------------------------------------------------------------------------

if (isset($_GET['ajax'])) {

    // -----------------------------
    // Set typing status (CSR typing)
    // -----------------------------
    if ($_GET['ajax'] === "set_typing" && isset($_POST['client_id'])) {
        $cid = intval($_POST['client_id']);
        $status = ($_POST['status'] === "1");

        $conn->prepare("
            INSERT INTO typing_status (client_id, csr_typing, updated_at)
            VALUES (:cid, :s, NOW())
            ON CONFLICT (client_id)
            DO UPDATE SET csr_typing = :s, updated_at = NOW()
        ")->execute([':cid' => $cid, ':s' => $status]);

        exit;
    }

    // -----------------------------
    // Get typing status
    // -----------------------------
    if ($_GET['ajax'] === "get_typing" && isset($_GET['client_id'])) {
        $cid = intval($_GET['client_id']);

        $st = $conn->prepare("
            SELECT csr_typing, client_typing
            FROM typing_status
            WHERE client_id = :cid
        ");
        $st->execute([':cid' => $cid]);

        echo json_encode($st->fetch(PDO::FETCH_ASSOC));
        exit;
    }

    // -----------------------------
    // Load clients
    // -----------------------------
    if ($_GET['ajax'] === "clients") {
        $tab = $_GET['tab'] ?? "all";

        $where = ($tab === "mine") ? " WHERE c.assigned_csr = :csr " : "";

        $q = $conn->prepare("
            SELECT c.id, c.name, c.assigned_csr,
                (SELECT email FROM users u WHERE u.full_name = c.name LIMIT 1) AS email
            FROM clients c
            $where
            ORDER BY c.id ASC
        ");

        if ($tab === "mine") $q->execute([':csr' => $csr_user]); else $q->execute();

        while ($row = $q->fetch(PDO::FETCH_ASSOC)) {

            $assigned = $row["assigned_csr"] ?: "Unassigned";
            $owned = ($assigned === $csr_user);

            if ($assigned === "Unassigned") {
                $btn = "<button class='pill green' onclick='assignClient({$row["id"]})'>＋</button>";
            } elseif ($owned) {
                $btn = "<button class='pill red' onclick='unassignClient({$row["id"]})'>−</button>";
            } else {
                $btn = "<button class='pill gray' disabled>🔒</button>";
            }

            echo "
            <div class='client-item'
                data-id='{$row["id"]}'
                data-name='".htmlspecialchars($row["name"],ENT_QUOTES)."'
                data-csr='{$assigned}'>
                
                <div>
                    <div class='client-name'>{$row["name"]}</div>
                    <div class='client-email'>".htmlspecialchars($row["email"] ?? "")."</div>
                    <div class='client-assign'>Assigned: {$assigned}</div>
                </div>

                <div>{$btn}</div>
            </div>";
        }
        exit;
    }

    // -----------------------------
    // Load chat messages
    // -----------------------------
    if ($_GET['ajax'] === "load_chat" && isset($_GET['client_id'])) {
        $cid = intval($_GET['client_id']);

        $c = $conn->prepare("
            SELECT ch.*, c.name AS client_name
            FROM chat ch
            JOIN clients c ON c.id = ch.client_id
            WHERE ch.client_id = :cid
            ORDER BY ch.created_at ASC
        ");
        $c->execute([':cid'=>$cid]);

        $rows = [];
        while ($r = $c->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                "message" => $r["message"],
                "sender_type" => $r["sender_type"],
                "time" => $r["created_at"],
                "client_name" => $r["client_name"],
                "csr_fullname" => $r["csr_fullname"],
                "assigned_csr" => $r["assigned_csr"]
            ];
        }

        echo json_encode($rows);
        exit;
    }

    // -----------------------------
    // Load client profile (avatar)
    // -----------------------------
    if ($_GET['ajax'] === "client_profile" && isset($_GET['name"])) {
        $name = trim($_GET['name']);

        $ps = $conn->prepare("SELECT email, gender, avatar FROM users WHERE full_name = :n LIMIT 1");
        $ps->execute([':n'=>$name]);
        $u = $ps->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "email" => $u["email"] ?? "",
            "gender" => $u["gender"] ?? "",
            "avatar" => $u["avatar"] ?? ""
        ]);
        exit;
    }

    echo "invalid";
    exit;
}

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
<div id="sidebar-overlay" onclick="toggleSidebar(false)"></div>
<div id="sidebar">
    <h2>CSR Menu</h2>
    <a onclick="switchTab('all')">💬 Chat Dashboard</a>
    <a onclick="switchTab('mine')">👤 My Clients</a>
    <a onclick="switchTab('rem')">⏰ Reminders</a>
    <a href="survey_responses.php">📝 Survey Responses</a>
    <a href="update_profile.php">👤 Edit Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<!-- HEADER -->
<header>
    <button id="hamb" onclick="toggleSidebar()">☰</button>
    <div class="brand">
        <img src="<?= $logoPath ?>" alt="Logo">
        <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
</header>

<!-- TOP TABS -->
<div class="tabs">
    <div id="tab-all" class="tab active" onclick="switchTab('all')">💬 All Clients</div>
    <div id="tab-mine" class="tab" onclick="switchTab('mine')">👤 My Clients</div>
    <div id="tab-rem" class="tab" onclick="switchTab('rem')">⏰ Reminders</div>
    <div class="tab" onclick="location.href='survey_responses.php'">📝 Surveys</div>
    <div class="tab" onclick="location.href='update_profile.php'">👤 Edit Profile</div>
</div>

<!-- MAIN -->
<div id="main">

    <!-- LEFT COLUMN -->
    <div id="client-col"></div>

    <!-- RIGHT COLUMN -->
    <div id="chat-col">

        <button id="collapseBtn" onclick="toggleRight()">…</button>

        <div id="chat-head">
            <div class="chat-title">
                <div id="chatAvatar" class="avatar"></div>
                <span id="chat-title">Select a client</span>
            </div>
        </div>

        <div id="messages"></div>

        <!-- Typing indicator -->
        <div id="typingIndicator" class="typing-bubble" style="display:none;">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>

        <!-- Input -->
        <div id="input" style="display:none;">
            <input id="msg" placeholder="Type a reply…" oninput="setTyping(1)">
            <button onclick="sendMsg()">Send</button>
        </div>

    </div>
</div>

<script>
let currentClient = 0;
let currentAssignee = "";
const me = <?= json_encode($csr_user) ?>;

// Sidebar
function toggleSidebar(force) {
    const s = document.getElementById("sidebar");
    const o = document.getElementById("sidebar-overlay");
    const open = s.classList.contains("active");

    if ((force === true) || (!open && force !== false)) {
        s.classList.add("active"); o.style.display = "block";
    } else {
        s.classList.remove("active"); o.style.display = "none";
    }
}

// Right column collapse
function toggleRight() {
    const col = document.getElementById("chat-col");
    const btn = document.getElementById("collapseBtn");
    if (col.classList.contains("collapsed")) {
        col.classList.remove("collapsed");
        btn.textContent = "…";
    } else {
        col.classList.add("collapsed");
        btn.textContent = "i";
    }
}

// Tabs
function switchTab(tab) {
    currentTab = tab === "rem" ? "all" : tab;
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    document.getElementById("tab-"+tab).classList.add("active");

    if (tab === "rem") {
        return; // skip for now
    }

    loadClients();
}

// Load client list
function loadClients() {
    fetch("csr_dashboard.php?ajax=clients&tab="+currentTab)
    .then(r=>r.text())
    .then(html=>{
        document.getElementById("client-col").innerHTML = html;
        document.querySelectorAll(".client-item").forEach(el=>{
            el.addEventListener("click", ()=>selectClient(el));
        });
    });
}

// Avatar
function setAvatar(name, gender, avatarFile) {
    const box = document.getElementById("chatAvatar");
    box.innerHTML = "";

    if (avatarFile) {
        let img = document.createElement("img");
        img.src = "uploads/"+avatarFile;
        box.appendChild(img);
        return;
    }

    if (gender === "female") {
        let img = document.createElement("img");
        img.src = "../penguin.png"; 
        box.appendChild(img);
    }
    else if (gender === "male") {
        let img = document.createElement("img");
        img.src = "../lion.png"; 
        box.appendChild(img);
    }
    else {
        box.textContent = name.split(" ").map(w=>w[0]).join("").toUpperCase();
    }
}

// Select client
function selectClient(el) {
    currentClient = parseInt(el.dataset.id);
    currentAssignee = el.dataset.csr;
    const name = el.dataset.name;

    document.getElementById("chat-title").textContent = name;
    document.getElementById("input").style.display = "flex";

    fetch("csr_dashboard.php?ajax=client_profile&name="+encodeURIComponent(name))
    .then(r=>r.json())
    .then(p=>{
        setAvatar(name, p.gender ? p.gender.toLowerCase() : null, p.avatar);
    });

    loadChat();
    getTyping();
}

// Load chat
function loadChat() {
    fetch("csr_dashboard.php?ajax=load_chat&client_id="+currentClient)
    .then(r=>r.json())
    .then(rows=>{
        const box = document.getElementById("messages");
        box.innerHTML = "";

        rows.forEach(m=>{
            const name = m.sender_type === "csr" ? (m.csr_fullname ?? "CSR") : m.client_name;
            box.innerHTML += `
                <div class="msg ${m.sender_type}">
                    <div class="bubble">
                        <strong>${name}:</strong> ${m.message}
                    </div>
                    <div class="meta">${new Date(m.time).toLocaleString()}</div>
                </div>
            `;
        });

        box.scrollTop = box.scrollHeight;
    });
}

// Typing indicator
let typingTimeout;

function setTyping(status) {
    // CSR is typing
    fetch("csr_dashboard.php?ajax=set_typing", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "client_id="+currentClient+"&status="+status
    });

    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
        // Stop typing after 2 seconds idle
        fetch("csr_dashboard.php?ajax=set_typing", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: "client_id="+currentClient+"&status=0"
        });
    }, 2000);
}

// Poll typing
function getTyping() {
    fetch("csr_dashboard.php?ajax=get_typing&client_id="+currentClient)
    .then(r=>r.json())
    .then(st=>{
        const bubble = document.getElementById("typingIndicator");

        if (st && st.client_typing) {
            bubble.style.display = "flex";
        } else {
            bubble.style.display = "none";
        }
    });
}

setInterval(() => {
    if (currentClient) {
        loadChat();
        getTyping();
    }
}, 1500);

// Send message
function sendMsg() {
    if (!currentClient) return;

    const msg = document.getElementById("msg").value.trim();
    if (!msg) return;

    fetch("../SKYTRUFIBER/save_chat.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "sender_type=csr&message="+encodeURIComponent(msg)+"&client_id="+currentClient
    }).then(()=>{
        document.getElementById("msg").value = "";
        setTyping(0); // stop typing
        loadChat();
    });
}

// Assign/unassign
function assignClient(id) {
    fetch("csr_dashboard.php?ajax=assign", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"client_id="+id
    }).then(()=>loadClients());
}

function unassignClient(id) {
    fetch("csr_dashboard.php?ajax=unassign", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"client_id="+id
    }).then(()=>loadClients());
}

loadClients();
</script>

</body>
</html>
