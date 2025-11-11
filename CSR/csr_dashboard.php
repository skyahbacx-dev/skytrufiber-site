<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// LOAD CSR DETAILS
$stmt = $conn->prepare("SELECT full_name, email FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$csr = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $csr['full_name'] ?? $csr_user;

$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ============================================================
   AJAX SECTION
   ============================================================ */
if (isset($_GET['ajax'])) {

    // LOAD CLIENTS
    if ($_GET['ajax'] === "clients") {
        $tab = $_GET['tab'] ?? "all";
        $condition = ($tab === "mine") ? "WHERE assigned_csr = :csr" : "";

        $q = $conn->prepare("
            SELECT id, name, assigned_csr,
                (SELECT email FROM users u WHERE u.full_name = c.name LIMIT 1) AS email
            FROM clients c
            $condition
            ORDER BY id ASC
        ");
        ($tab === "mine") ? $q->execute([':csr' => $csr_user]) : $q->execute();

        while($row = $q->fetch(PDO::FETCH_ASSOC)){

            $assigned = $row["assigned_csr"] ?: "Unassigned";
            $btn="";
            if ($assigned === "Unassigned")
                $btn = "<button class='pill green' onclick='assignClient({$row["id"]})'>＋</button>";
            else if ($assigned === $csr_user)
                $btn = "<button class='pill red' onclick='unassignClient({$row["id"]})'>−</button>";
            else
                $btn = "<button class='pill gray' disabled>🔒</button>";

            echo "
            <div class='client-item' data-id='{$row["id"]}' data-name='".htmlspecialchars($row["name"],ENT_QUOTES)."' data-csr='{$assigned}'>
                <div>
                    <div class='client-name'>{$row["name"]}</div>
                    <div class='client-email'>".htmlspecialchars($row["email"] ?? "")."</div>
                    <div class='client-assign'>Assigned: {$assigned}</div>
                </div>
                <div>$btn</div>
            </div>";
        }
        exit;
    }

    // LOAD CHAT
    if ($_GET['ajax'] === "load_chat" && isset($_GET['client_id'])) {
        $cid = (int)$_GET['client_id'];

        $q = $conn->prepare("
            SELECT ch.*, c.name as client_name
            FROM chat ch
            JOIN clients c ON c.id = ch.client_id
            WHERE ch.client_id = :cid
            ORDER BY ch.created_at ASC
        ");
        $q->execute([':cid'=>$cid]);

        $res = [];
        while($r = $q->fetch(PDO::FETCH_ASSOC)) {
            $res[] = $r;
        }

        echo json_encode($res);
        exit;
    }

    // LOAD PROFILE
    if ($_GET['ajax'] === "client_profile" && isset($_GET['name'])) {
        $name = $_GET['name'];
        $q = $conn->prepare("SELECT email, gender, avatar FROM users WHERE full_name = :n LIMIT 1");
        $q->execute([':n'=>$name]);
        echo json_encode($q->fetch(PDO::FETCH_ASSOC) ?: []);
        exit;
    }

    // ASSIGN
    if ($_GET['ajax'] === "assign" && isset($_POST['client_id'])) {
        $cid = (int)$_POST['client_id'];
        $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id")
             ->execute([':csr'=>$csr_user, ':id'=>$cid]);
        echo "ok";
        exit;
    }

    // UNASSIGN
    if ($_GET['ajax'] === "unassign" && isset($_POST['client_id'])) {
        $cid = (int)$_POST['client_id'];
        $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned' WHERE id = :id AND assigned_csr = :csr")
             ->execute([':id'=>$cid, ':csr'=>$csr_user]);
        echo "ok";
        exit;
    }

    // MARK SEEN
    if ($_GET['ajax'] === "seen") {
        $cid = (int)$_GET['client_id'];
        $conn->prepare("UPDATE chat SET seen = TRUE WHERE client_id = :cid AND sender_type = 'client'")
             ->execute([':cid'=>$cid]);
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
        <img src="<?= $logoPath ?>" alt="logo">
        <span>CSR Dashboard — <?= htmlspecialchars($csr_fullname) ?></span>
    </div>
    <div id="darkToggle" onclick="toggleDarkMode()">🌙</div>
</header>

<!-- TABS -->
<div class="tabs">
    <div id="tab-all" class="tab active" onclick="switchTab('all')">💬 All Clients</div>
    <div id="tab-mine" class="tab" onclick="switchTab('mine')">👤 My Clients</div>
    <div id="tab-rem" class="tab" onclick="switchTab('rem')">⏰ Reminders</div>
    <div class="tab" onclick="location.href='survey_responses.php'">📝 Surveys</div>
    <div class="tab" onclick="location.href='update_profile.php'">👤 Profile</div>
</div>

<!-- MAIN -->
<div id="main">
    <!-- LEFT -->
    <div id="client-col"></div>

    <!-- RIGHT -->
    <div id="chat-col">
        <button id="collapseBtn" onclick="toggleRight()">●</button>

        <!-- CHAT HEADER -->
        <div id="chat-head">
            <div class="chat-title">
                <div id="chatAvatar" class="avatar"></div>
                <span id="chat-title">Select a Client</span>
            </div>
            <div class="info-dot">i</div>
        </div>

        <!-- MESSAGES -->
        <div id="messages"></div>
        <div id="typingIndicator">typing…</div>

        <!-- INPUT -->
        <div id="input" style="display:none;">
            <button id="emojiBtn" type="button">😊</button>
            <div id="emojiPanel"></div>
            <input id="msg" placeholder="Type a reply…">
            <button onclick="sendMsg()">Send</button>
        </div>
    </div>
</div>

<script>
let currentTab="all", currentClient=0, currentAssignee="";
const me = <?= json_encode($csr_user) ?>;

// Sidebar
function toggleSidebar(force){
    const s=document.getElementById("sidebar");
    const o=document.getElementById("sidebar-overlay");
    const open=s.classList.contains("active");

    let openNow = (force===true || (!open && force!==false));
    if(openNow){ s.classList.add("active"); o.style.display="block"; }
    else { s.classList.remove("active"); o.style.display="none"; }
}

// Collapse chat
function toggleRight(){
    const col=document.getElementById("chat-col");
    if(col.classList.contains("collapsed")){
        col.classList.remove("collapsed");
    } else {
        col.classList.add("collapsed");
    }
}

// Switch tab
function switchTab(tab){
    document.querySelectorAll(".tab").forEach(t=>t.classList.remove("active"));
    document.getElementById("tab-"+tab).classList.add("active");
    currentTab=tab;
    loadClients();
}

// Load clients
function loadClients(){
    fetch("csr_dashboard.php?ajax=clients&tab="+currentTab)
    .then(r=>r.text())
    .then(html=>{
        document.getElementById("client-col").innerHTML=html;
        document.querySelectorAll(".client-item").forEach(x=>{
            x.onclick=()=>selectClient(x);
        });
    });
}

// Avatar
function setAvatar(name, gender, avatar){
    const box=document.getElementById("chatAvatar");
    box.innerHTML="";

    let img=document.createElement("img");

    if(avatar){
        img.src="uploads/"+avatar;
    } else if(gender==="female"){
        img.src="../penguin.png";
    } else if(gender==="male"){
        img.src="../lion.png";
    } else {
        box.textContent=name[0].toUpperCase();
        return;
    }
    box.appendChild(img);
}

// Select client
function selectClient(el){
    currentClient = el.dataset.id;
    currentAssignee = el.dataset.csr;
    const name = el.dataset.name;

    document.getElementById("chat-title").textContent=name;
    document.getElementById("input").style.display="flex";

    fetch("csr_dashboard.php?ajax=client_profile&name="+encodeURIComponent(name))
    .then(r=>r.json())
    .then(p=>{
        setAvatar(name, p.gender, p.avatar);
    });

    loadChat();
}

// Load chat
function loadChat(){
    if(!currentClient) return;

    fetch("csr_dashboard.php?ajax=load_chat&client_id="+currentClient)
    .then(r=>r.json())
    .then(rows=>{
        const box=document.getElementById("messages");
        box.innerHTML="";

        rows.forEach(m=>{
            let seen="";
            if(m.sender_type==="csr"){
                seen = m.seen==true ? "✅ Seen" : "✓ Delivered";
            }
            box.innerHTML += `
            <div class="msg ${m.sender_type}">
                <div class="bubble">
                    <strong>${m.sender_type==="csr"?"CSR":m.client_name}:</strong> ${m.message}
                </div>
                <div class="meta">${new Date(m.created_at).toLocaleTimeString()} ${seen}</div>
            </div>`;
        });

        box.scrollTop = box.scrollHeight;

        // Mark client messages as seen
        fetch("csr_dashboard.php?ajax=seen&client_id="+currentClient);
    });
}

// Send message
function sendMsg(){
    if(!currentClient) return;
    const txt=document.getElementById("msg");
    const msg=txt.value.trim();
    if(!msg) return;

    fetch("../SKYTRUFIBER/save_chat.php", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"sender_type=csr&message="+encodeURIComponent(msg)+"&client_id="+currentClient
    }).then(()=>{
        txt.value="";
        loadChat();
    });
}

// Dark Mode
function toggleDarkMode(){
    document.body.classList.toggle("dark");
    localStorage.setItem("dark", document.body.classList.contains("dark"));
}

if(localStorage.getItem("dark")==="true"){
    document.body.classList.add("dark");
}

// Emoji picker
const emojiPanel=document.getElementById("emojiPanel");
emojiPanel.style.display="none";

const emojiList="😀😁😂🤣😃😄😅😉😊😍😘😎🤩🤔🙄😇😭😡👍👎🙏👏🔥✨❤️💯".split("");
emojiList.forEach(e=>{
    let span=document.createElement("span");
    span.textContent=e;
    span.className="emoji";
    span.onclick=()=>{ document.getElementById("msg").value+=e; emojiPanel.style.display="none"; };
    emojiPanel.appendChild(span);
});

document.getElementById("emojiBtn").onclick=()=> {
    emojiPanel.style.display = emojiPanel.style.display==="none" ? "flex" : "none";
};

// Typing
document.getElementById("msg").addEventListener("input", ()=>{
    document.getElementById("typingIndicator").style.display="block";
    clearTimeout(window.typingTimer);
    window.typingTimer=setTimeout(()=>{
        document.getElementById("typingIndicator").style.display="none";
    },1500);
});

// Auto refresh
setInterval(()=>{
    if(currentClient) loadChat();
}, 4000);

loadClients();
</script>

</body>
</html>
