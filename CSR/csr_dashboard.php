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
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

// ─────────────────────────────────────────────────────────────────────
// AJAX HANDLERS
// ─────────────────────────────────────────────────────────────────────

if (isset($_GET['ajax'])) {

    // Load clients
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

        if ($tab === "mine") {
            $q->execute([':csr' => $csr_user]);
        } else {
            $q->execute();
        }

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

    // Load chat messages
    if ($_GET['ajax'] === "load_chat" && isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];

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

    // Load client profile (email + gender + avatar)
    if ($_GET['ajax'] === "client_profile" && isset($_GET['name'])) {
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

    // Assign client
    if ($_GET['ajax'] === "assign" && isset($_POST['client_id'])) {
        $cid = (int)$_POST['client_id'];

        $chk = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :i");
        $chk->execute([':i'=>$cid]);
        $cur = $chk->fetch(PDO::FETCH_ASSOC);

        if ($cur && $cur["assigned_csr"] && $cur["assigned_csr"] !== "Unassigned") {
            echo "taken";
            exit;
        }

        $u = $conn->prepare("UPDATE clients SET assigned_csr = :c WHERE id = :i");
        $u->execute([':c'=>$csr_user, ':i'=>$cid]);
        echo "ok";
        exit;
    }

    // Unassign client
    if ($_GET['ajax'] === "unassign" && isset($_POST['client_id'])) {
        $cid = (int)$_POST['client_id'];

        $u = $conn->prepare("
            UPDATE clients
            SET assigned_csr = 'Unassigned'
            WHERE id = :i AND assigned_csr = :c
        ");
        $u->execute([':i'=>$cid, ':c'=>$csr_user]);

        echo "ok";
        exit;
    }

    // Reminders list
    if ($_GET['ajax'] === "reminders") {

        $search = trim($_GET['q'] ?? "");
        $rows = [];

        $u = $conn->query("
            SELECT id, full_name, email, date_installed
            FROM users
            WHERE email IS NOT NULL
            ORDER BY full_name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $today = new DateTime('today');

        foreach ($u as $usr) {
            if (!$usr["date_installed"]) continue;

            $di = new DateTime($usr["date_installed"]);
            $dueDay = (int)$di->format('d');

            $base = new DateTime('first day of this month');
            $due = $base->setDate($base->format('Y'), $base->format('m'), min($dueDay, 28));

            if ((int)$today->format('d') > (int)$due->format('d')) {
                $base->modify('+1 month');
                $due = $base->setDate($base->format('Y'), $base->format('m'), min($dueDay, 28));
            }

            $oneWeek = (clone $due)->modify('-7 days');
            $threeDay = (clone $due)->modify('-3 days');

            $cycle = $due->format('Y-m-d');

            $st = $conn->prepare("
                SELECT reminder_type, status
                FROM reminders
                WHERE client_id = :cid AND cycle_date = :cy
            ");
            $st->execute([':cid'=>$usr['id'], ':cy'=>$cycle]);

            $sent = [];
            foreach ($st as $x) {
                $sent[$x['reminder_type']] = $x['status'];
            }

            $badges = [];

            if ($today <= $oneWeek && $today->diff($oneWeek)->days <= 7) {
                $badges[] = ["type"=>"1_WEEK", "status"=>$sent["1_WEEK"] ?? "upcoming", "date"=>$oneWeek->format("Y-m-d")];
            }
            if ($today <= $threeDay && $today->diff($threeDay)->days <= 7) {
                $badges[] = ["type"=>"3_DAYS", "status"=>$sent["3_DAYS"] ?? "upcoming", "date"=>$threeDay->format("Y-m-d")];
            }

            if (!$badges) continue;

            if ($search) {
                $hay = strtolower($usr["full_name"]." ".$usr["email"]);
                if (strpos($hay, strtolower($search)) === false) continue;
            }

            $rows[] = [
                "name"=>$usr["full_name"],
                "email"=>$usr["email"],
                "due"=>$due->format("Y-m-d"),
                "banners"=>$badges
            ];
        }

        echo json_encode($rows);
        exit;
    }

    // default
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

<!-- MAIN LAYOUT -->
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
            <div id="chat-info" class="info-dot">i</div>
        </div>

        <div id="messages"></div>

        <div id="input" style="display:none;">
            <input id="msg" placeholder="Type a reply…">
            <button onclick="sendMsg()">Send</button>
        </div>

        <!-- Reminders panel -->
        <div id="reminders">
            <div id="rem-filter">
                <input id="rem-q" placeholder="Search..." onkeyup="loadReminders()">
            </div>
            <div id="rem-list"></div>
        </div>

    </div>
</div>

<script>
let currentTab = "all";
let currentClient = 0;
let currentAssignee = "";
const me = <?= json_encode($csr_user) ?>;

/* Sidebar */
function toggleSidebar(force) {
    const s = document.getElementById("sidebar");
    const o = document.getElementById("sidebar-overlay");
    const open = s.classList.contains("active");
    let willOpen = force === true || (!open && force !== false);

    if (willOpen) {
        s.classList.add("active");
        o.style.display = "block";
    } else {
        s.classList.remove("active");
        o.style.display = "none";
    }
}

/* Right column collapse */
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

/* Tab switching */
function switchTab(tab) {
    currentTab = tab === "rem" ? "all" : tab;
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    document.getElementById("tab-"+tab).classList.add("active");

    if (tab === "rem") {
        document.getElementById("chat-head").style.display = "none";
        document.getElementById("messages").style.display = "none";
        document.getElementById("input").style.display = "none";
        document.getElementById("reminders").style.display = "block";
        loadReminders();
    } else {
        document.getElementById("chat-head").style.display = "flex";
        document.getElementById("messages").style.display = "block";
        document.getElementById("reminders").style.display = "none";
        loadClients();
    }
}

/* Load client list */
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

/* Avatar handler */
function setAvatar(name, gender, avatarFile) {
    const box = document.getElementById("chatAvatar");
    box.innerHTML = "";

    let img = document.createElement("img");

    if (avatarFile) {
        img.src = "uploads/" + avatarFile;
    } else if (gender === "female") {
        img.src = "assets/penguin.png";
    } else if (gender === "male") {
        img.src = "assets/lion.png";
    } else {
        box.textContent = name.split(" ").map(w=>w[0]).join("").toUpperCase();
        return;
    }

    box.appendChild(img);
}

/* Select client */
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
}

/* Chat loading */
function loadChat() {
    if (!currentClient) return;

    fetch("csr_dashboard.php?ajax=load_chat&client_id="+currentClient)
    .then(r=>r.json())
    .then(rows=>{
        const box = document.getElementById("messages");
        box.innerHTML = "";

        rows.forEach(m=>{
            const name = (m.sender_type === "csr") ? (m.csr_fullname ?? "CSR") : m.client_name;
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

/* Send message */
function sendMsg() {
    if (!currentClient) return;

    if (currentAssignee !== "Unassigned" && currentAssignee !== me) {
        alert("This client is assigned to another CSR.");
        return;
    }

    const msg = document.getElementById("msg").value.trim();
    if (!msg) return;

    fetch("../SKYTRUFIBER/save_chat.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "sender_type=csr&message="+encodeURIComponent(msg)+"&client_id="+currentClient
    }).then(()=>{
        document.getElementById("msg").value = "";
        loadChat();
    });
}

/* Assign/Unassign */
function assignClient(id) {
    fetch("csr_dashboard.php?ajax=assign", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"client_id="+id
    })
    .then(r=>r.text())
    .then(t=>{
        if (t==="taken") alert("Already assigned.");
        loadClients();
    });
}

function unassignClient(id) {
    if (!confirm("Unassign this client?")) return;
    fetch("csr_dashboard.php?ajax=unassign", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"client_id="+id
    }).then(()=>loadClients());
}

/* Reminders */
function loadReminders() {
    const q = document.getElementById("rem-q").value;

    fetch("csr_dashboard.php?ajax=reminders&q="+encodeURIComponent(q))
    .then(r=>r.json())
    .then(list=>{
        const box = document.getElementById("rem-list");
        box.innerHTML = "";

        if (!list.length) {
            box.innerHTML = "<div class='card'>No reminders</div>";
            return;
        }

        list.forEach(item=>{
            let badges = "";
            item.banners.forEach(b=>{
                let cls = b.status === "sent" ? "sent" : (b.status === "due" ? "due" : "upcoming");
                badges += `<span class="badge ${cls}">${b.type} — ${b.status} (${b.date})</span>`;
            });

            box.innerHTML += `
                <div class="card">
                    <strong>${item.name}</strong> (${item.email})<br>
                    Due: <b>${item.due}</b><br>
                    ${badges}
                </div>
            `;
        });
    });
}

loadClients();
setInterval(()=>{
    if (currentClient) loadChat();
    if (document.getElementById("reminders").style.display !== "none") loadReminders();
}, 5000);
</script>

</body>
</html>
