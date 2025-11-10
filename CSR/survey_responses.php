<?php
session_start();
include '../db_connect.php';

// Validate login
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}
$csr_user = $_SESSION['csr_user'];

// Get CSR name
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username=:u LIMIT 1");
$stmt->execute([':u'=>$csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

// Logo
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

// ======================== AJAX SECTION ============================
if (isset($_GET['ajax'])) {

    // ✅ LOAD ONLY survey_responses TABLE
    if ($_GET['ajax'] === 'load') {
        $search = "%" . ($_GET['search'] ?? '') . "%";
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';

        $params = [':search'=> $search];

        $q = "
            SELECT id, client_name, account_number, district, location, feedback AS remarks, email, created_at,
                   'survey_responses' AS source
            FROM survey_responses
            WHERE (
                client_name ILIKE :search OR
                account_number ILIKE :search OR
                district ILIKE :search OR
                location ILIKE :search OR
                feedback ILIKE :search OR
                email ILIKE :search
            )
        ";

        if ($from && $to) {
            $q .= " AND DATE(created_at) BETWEEN :from AND :to";
            $params[':from'] = $from;
            $params[':to'] = $to;
        }

        $q .= " ORDER BY created_at DESC";

        $stmt = $conn->prepare($q);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // ✅ UPDATE survey_responses RECORDS
    if ($_GET['ajax'] === 'update' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $client = trim($_POST['client_name']);
        $account = trim($_POST['account_number']);
        $district = trim($_POST['district']);
        $location = trim($_POST['location']);
        $feedback = trim($_POST['feedback']);
        $email = trim($_POST['email']);

        $stmt = $conn->prepare("
            UPDATE survey_responses
            SET client_name=:c,
                account_number=:a,
                district=:d,
                location=:l,
                feedback=:f,
                email=:e
            WHERE id=:id
        ");

        $ok = $stmt->execute([
            ':c'=>$client, ':a'=>$account, ':d'=>$district,
            ':l'=>$location, ':f'=>$feedback, ':e'=>$email, ':id'=>$id
        ]);

        echo $ok ? "ok" : "fail";
        exit;
    }

    http_response_code(400);
    echo "bad request";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Survey & Feedback — <?= htmlspecialchars($csr_fullname) ?></title>

<style>
/* --------- GLOBAL ----------- */
body {
    margin:0;
    font-family:Segoe UI,Arial,sans-serif;
    background:#f4FFF4;
    height:100vh;
    overflow:hidden;
}
header {
    height:60px;
    background:#009900;
    color:white;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 18px;
    font-weight:700;
}

#hamburger {
    font-size:26px;
    background:none;
    border:none;
    color:white;
    cursor:pointer;
}

/* --------- SIDEBAR ----------- */
#sidebar {
    width:260px;
    background:#006b00;
    color:white;
    height:100vh;
    position:fixed;
    left:-260px;
    top:0;
    transition:0.25s;
    z-index:1000;
}
#sidebar.active {
    left:0;
}
#sidebar h2 {
    margin:0;
    padding:20px;
    text-align:center;
    background:#005c00;
}
#sidebar a {
    display:block;
    padding:14px 18px;
    text-decoration:none;
    color:white;
    font-weight:600;
}
#sidebar a:hover {
    background:#00aa00;
}

/* --------- TABS ----------- */
#tabs {
    display:flex;
    gap:10px;
    padding:12px 18px;
    background:#eaffea;
    border-bottom:1px solid #cce5cc;
}
.tab {
    padding:8px 16px;
    border-radius:6px;
    cursor:pointer;
    font-weight:700;
    color:#006b00;
}
.tab.active {
    background:#006b00;
    color:white;
}

/* -------- CONTENT -------- */
#main {
    padding:20px;
    overflow-y:auto;
    height:calc(100vh - 120px);
}

table {
    width:100%;
    border-collapse:collapse;
    box-shadow:0 3px 10px rgba(0,0,0,0.1);
}
th {
    background:#009900;
    color:white;
    position:sticky;
    top:0;
}
th, td {
    padding:10px;
    border-bottom:1px solid #eee;
}
tr:hover {
    background:#f1fff1;
}

/* --- EDIT MODAL --- */
#editModal {
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.4);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:2000;
}
.modal-box {
    width:90%;
    max-width:500px;
    background:white;
    padding:20px;
    border-radius:10px;
}
.modal-box input,
.modal-box textarea {
    width:100%;
    padding:10px;
    margin:5px 0 10px;
    border-radius:6px;
    border:1px solid #ccc;
}
.modal-box button {
    padding:10px 14px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:600;
}
#saveBtn { background:#009900; color:white; }
#cancelBtn { background:#ccc; }

</style>
</head>

<body>

<!-- SIDEBAR -->
<div id="sidebar">
    <h2>Menu</h2>
    <a href="csr_dashboard.php">💬 All Clients</a>
    <a href="csr_dashboard.php?tab=mine">👤 My Clients</a>
    <a href="csr_dashboard.php?tab=rem">⏰ Reminders</a>
    <a style="background:#00b300;">📝 Survey & Feedback</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<!-- HEADER -->
<header>
    <button id="hamburger" onclick="toggleSidebar()">☰</button>
    <div>Survey & Feedback — <?= htmlspecialchars($csr_fullname) ?></div>
</header>

<!-- TABS -->
<div id="tabs">
    <div class="tab" onclick="location.href='csr_dashboard.php'">💬 All Clients</div>
    <div class="tab" onclick="location.href='csr_dashboard.php?tab=mine'">👤 My Clients</div>
    <div class="tab" onclick="location.href='csr_dashboard.php?tab=rem'">⏰ Reminders</div>
    <div class="tab active">📝 Survey & Feedback</div>
</div>

<div id="main">
    <div style="display:flex;gap:10px;margin-bottom:10px;align-items:center;">
        <label>Search:</label>
        <input id="searchBox" type="text" placeholder="Client, Account, Feedback..." style="padding:8px;border:1px solid #ccc;border-radius:6px;width:220px;">
        <label>From:</label>
        <input id="fromDate" type="date">
        <label>To:</label>
        <input id="toDate" type="date">
        <button onclick="loadTable()" style="padding:8px 14px;background:#009900;color:white;border:none;border-radius:6px;">Filter</button>
    </div>

    <div id="table-container">Loading…</div>
</div>

<!-- EDIT MODAL -->
<div id="editModal">
    <div class="modal-box">
        <h3>Edit Survey Response</h3>

        <input type="hidden" id="editId">

        <label>Client Name</label>
        <input id="editClient">

        <label>Account Number</label>
        <input id="editAccount">

        <label>District</label>
        <input id="editDistrict">

        <label>Location</label>
        <input id="editLocation">

        <label>Email</label>
        <input id="editEmail">

        <label>Feedback</label>
        <textarea id="editFeedback" rows="4"></textarea>

        <div style="text-align:right;margin-top:10px;">
            <button id="cancelBtn" onclick="closeModal()">Cancel</button>
            <button id="saveBtn" onclick="saveChanges()">Save</button>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

// Load table
function loadTable() {
    const s = encodeURIComponent(document.getElementById('searchBox').value.trim());
    const f = document.getElementById('fromDate').value;
    const t = document.getElementById('toDate').value;

    fetch(`survey_responses.php?ajax=load&search=${s}&from=${f}&to=${t}`)
        .then(r=>r.json())
        .then(rows=>{
            let html = "<table><thead><tr>" +
                "<th>#</th><th>Client</th><th>Account</th><th>District</th><th>Location</th><th>Email</th><th>Feedback</th><th>Date</th><th>Action</th>" +
                "</tr></thead><tbody>";

            if (!rows.length) {
                html += "<tr><td colspan='9' style='text-align:center;padding:20px;color:#777;'>No survey responses found.</td></tr>";
            } else {
                rows.forEach((r,i)=>{
                    html += `<tr>
                        <td>${i+1}</td>
                        <td>${r.client_name}</td>
                        <td>${r.account_number ?? ''}</td>
                        <td>${r.district ?? ''}</td>
                        <td>${r.location ?? ''}</td>
                        <td>${r.email ?? ''}</td>
                        <td>${r.remarks ?? ''}</td>
                        <td>${new Date(r.created_at).toLocaleString()}</td>
                        <td><button onclick="openEdit(${r.id}, '${escape(r.client_name)}', '${escape(r.account_number)}', '${escape(r.district)}', '${escape(r.location)}', '${escape(r.email)}', '${escape(r.remarks)}')" style="background:#009900;color:white;border:none;padding:6px 10px;border-radius:6px;">Edit</button></td>
                    </tr>`;
                });
            }

            html += "</tbody></table>";

            document.getElementById('table-container').innerHTML = html;
        });
}

function escape(text) {
    return (text ?? '').replace(/'/g,"&#39;").replace(/"/g,"&quot;");
}

function openEdit(id, client, account, district, location, email, feedback) {
    document.getElementById('editId').value = id;
    document.getElementById('editClient').value = client;
    document.getElementById('editAccount').value = account;
    document.getElementById('editDistrict').value = district;
    document.getElementById('editLocation').value = location;
    document.getElementById('editEmail').value = email;
    document.getElementById('editFeedback').value = feedback;

    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

function saveChanges() {
    const id = document.getElementById('editId').value;
    const data = new URLSearchParams();
    data.append("id", id);
    data.append("client_name", document.getElementById('editClient').value);
    data.append("account_number", document.getElementById('editAccount').value);
    data.append("district", document.getElementById('editDistrict').value);
    data.append("location", document.getElementById('editLocation').value);
    data.append("email", document.getElementById('editEmail').value);
    data.append("feedback", document.getElementById('editFeedback').value);

    fetch("survey_responses.php?ajax=update", {
        method:"POST",
        headers:{'Content-Type':"application/x-www-form-urlencoded"},
        body:data.toString()
    })
    .then(r=>r.text())
    .then(resp=>{
        if (resp === "ok") {
            alert("✅ Updated successfully!");
            closeModal();
            loadTable();
        } else {
            alert("❌ Update failed!");
        }
    });
}

window.onload = loadTable;
</script>

</body>
</html>
