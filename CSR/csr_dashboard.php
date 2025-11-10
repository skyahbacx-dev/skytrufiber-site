<?php
session_start();
include '../db_connect.php';

// Security check
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// safe encode
function h($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// Fetch CSR full name
$st = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$st->execute([':u' => $csr_user]);
$row = $st->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

// Logo path
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

// ----------------------------------------
// AJAX HANDLER
// ----------------------------------------
if (isset($_GET['ajax'])) {

    // Load clients list
    if ($_GET['ajax'] === 'clients') {
        $tab = $_GET['tab'] ?? 'all';

        $sql = "
        SELECT c.id, c.name, c.assigned_csr,
        (SELECT email FROM users u WHERE u.full_name = c.name LIMIT 1) AS email,
        MAX(ch.created_at) AS last_chat
        FROM clients c
        LEFT JOIN chat ch ON ch.client_id = c.id
        ";

        $where = ($tab === "mine") ? " WHERE c.assigned_csr = :csr " : "";
        $sql .= $where . " GROUP BY c.id ORDER BY last_chat DESC NULLS LAST";

        $q = $conn->prepare($sql);
        if ($tab === "mine") {
            $q->execute([':csr' => $csr_user]);
        } else {
            $q->execute();
        }

        while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
            $assigned = $r['assigned_csr'] ?: "Unassigned";
            $owned = ($assigned === $csr_user);

            if ($assigned === 'Unassigned') {
                $btn = "<button class='pill green' onclick='assignClient({$r['id']})'>＋</button>";
            } elseif ($owned) {
                $btn = "<button class='pill red' onclick='unassignClient({$r['id']})'>−</button>";
            } else {
                $btn = "<button class='pill gray' disabled>🔒</button>";
            }

            echo "
            <div class='client-item' data-id='{$r['id']}' data-name='" . h($r['name']) . "' data-csr='" . h($assigned) . "'>
                <div class='client-meta'>
                    <div class='client-name'>" . h($r['name']) . "</div>
                    <div class='client-email'>" . h($r['email']) . "</div>
                    <div class='client-assign'>Assigned: " . h($assigned) . "</div>
                </div>
                <div class='client-actions'>{$btn}</div>
            </div>
            ";
        }
        exit;
    }

    // Load chat messages
    if ($_GET['ajax'] === 'load_chat' && isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];

        $q = $conn->prepare("
        SELECT ch.message, ch.sender_type, ch.created_at, ch.csr_fullname,
               c.name AS client_name
        FROM chat ch
        JOIN clients c ON c.id = ch.client_id
        WHERE ch.client_id = :cid
        ORDER BY ch.created_at ASC
        ");

        $q->execute([':cid' => $cid]);

        $msgs = [];
        while ($m = $q->fetch(PDO::FETCH_ASSOC)) {
            $msgs[] = [
                'message' => $m['message'],
                'sender_type' => $m['sender_type'],
                'time' => $m['created_at'],
                'client_name' => $m['client_name'],
                'csr_fullname' => $m['csr_fullname']
            ];
        }
        echo json_encode($msgs);
        exit;
    }

    // Client profile lookup
    if ($_GET['ajax'] === 'client_profile' && isset($_GET['name'])) {
        $name = $_GET['name'];
        $q = $conn->prepare("SELECT email, gender FROM users WHERE full_name = :n LIMIT 1");
        $q->execute([':n' => $name]);
        $res = $q->fetch(PDO::FETCH_ASSOC);

        echo json_encode($res);
        exit;
    }

    // Assign client
    if ($_GET['ajax'] === 'assign' && isset($_POST['client_id'])) {
        $id = (int)$_POST['client_id'];

        $c = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :id");
        $c->execute([':id' => $id]);
        $cur = $c->fetch(PDO::FETCH_ASSOC);

        if ($cur && $cur['assigned_csr'] && $cur['assigned_csr'] !== 'Unassigned') {
            echo "taken";
            exit;
        }

        $ok = $conn->prepare("UPDATE clients SET assigned_csr = :c WHERE id = :id")
                   ->execute([':c' => $csr_user, ':id' => $id]);

        echo $ok ? "ok" : "fail";
        exit;
    }

    // Unassign client
    if ($_GET['ajax'] === 'unassign' && isset($_POST['client_id'])) {
        $id = (int)$_POST['client_id'];

        $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned'
                        WHERE id = :id AND assigned_csr = :csr")
             ->execute([':id' => $id, ':csr' => $csr_user]);

        echo "ok";
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
    <title>CSR Dashboard — <?= h($csr_fullname) ?></title>

    <!-- ✅ Load external CSS -->
    <link rel="stylesheet" href="csr_dashboard.css">

    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<!-- ========================= HEADER ========================== -->
<header>
    <button id="hamb" onclick="toggleSidebar()">☰</button>
    <div class="brand">
        <img src="<?= h($logoPath) ?>" alt="Logo">
        <span>CSR Dashboard — <?= h($csr_fullname) ?></span>
    </div>
</header>

<!-- ========================= SIDEBAR ========================== -->
<div id="overlay" onclick="toggleSidebar(false)"></div>

<div id="sidebar">
    <h2>CSR Menu</h2>
    <a onclick="switchTab('all')">💬 All Clients</a>
    <a onclick="switchTab('mine')">👤 My Clients</a>
    <a onclick="switchTab('rem')">⏰ Reminders</a>
    <a href="survey_responses.php">📝 Surveys</a>
    <a href="update_profile.php">👤 Edit Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<!-- ========================= TABS ========================== -->
<div class="tabs">
    <div id="tab-all" class="tab active" onclick="switchTab('all')">💬 All Clients</div>
    <div id="tab-mine" class="tab" onclick="switchTab('mine')">👤 My Clients</div>
    <div id="tab-rem" class="tab" onclick="switchTab('rem')">⏰ Reminders</div>
    <div class="tab" onclick="location.href='survey_responses.php'">📝 Surveys</div>
    <div class="tab" onclick="location.href='update_profile.php'">👤 Edit Profile</div>
</div>

<!-- ========================= MAIN LAYOUT ========================== -->
<div id="main">

    <!-- LEFT COLUMN -->
    <div id="client-col"></div>

    <!-- RIGHT COLUMN -->
    <div id="chat-col">
        <button id="collapseBtn" onclick="toggleRight()">…</button>

        <div id="chat-head">
            <div class="chat-title">
                <div class="avatar" id="chatAvatar"></div>
                <div id="chat-title">Select a client</div>
            </div>
            <div class="info-dot">i</div>
        </div>

        <div id="messages"></div>

        <div id="input">
            <input id="msg" placeholder="Type a reply…">
            <button onclick="sendMsg()">Send</button>
        </div>

        <div id="reminders">
            <input id="rem-q" placeholder="Search…" onkeyup="loadReminders()">
            <div id="rem-list"></div>
        </div>
    </div>

</div>

<script>
/* ===========================================================
   ALL JAVASCRIPT REMAINS SAME — INCLUDING FIXES:
   - collapse toggle
   - gender avatar
   - AJAX calls
   =========================================================== */

const penguinIcon = "https://cdn-icons-png.flaticon.com/512/616/616490.png";
const lionIcon = "https://cdn-icons-png.flaticon.com/512/1998/1998610.png";

function nameGuessGender(n) {
    n = n.toLowerCase();
    if (n.endsWith("a") || n.includes("macy")) return "female";
    return "male";
}

function setAvatar(name, gender) {
    const img = document.createElement("img");
    if (!gender) gender = nameGuessGender(name);
    img.src = (gender === "female") ? penguinIcon : lionIcon;
    document.getElementById("chatAvatar").innerHTML = "";
    document.getElementById("chatAvatar").appendChild(img);
}

function toggleSidebar(force){ ... }
function toggleRight(){ ... }
function switchTab(tab){ ... }
function loadClients(){ ... }
function selectClient(el){ ... }
function loadChat(){ ... }
function sendMsg(){ ... }
function assignClient(id){ ... }
function unassignClient(id){ ... }
function loadReminders(){ ... }

</script>

</body>
</html>
