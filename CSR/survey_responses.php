<?php
session_start();
include '../db_connect.php';

// Verify CSR login
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Get CSR full name
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u LIMIT 1");
$stmt->execute([':u' => $csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

// Logo fallback
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ===== AJAX HANDLERS ===== */
if (isset($_GET['ajax'])) {

    /* === LOAD SURVEY_RESPONSES ONLY === */
    if ($_GET['ajax'] === 'load') {

        $search = "%" . ($_GET['search'] ?? '') . "%";
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';

        $params = [':search' => $search];

        $query = "
            SELECT id, client_name, account_number, district, location, feedback, created_at
            FROM survey_responses
            WHERE (client_name ILIKE :search
                OR account_number ILIKE :search
                OR district ILIKE :search
                OR location ILIKE :search
                OR feedback ILIKE :search)
        ";

        if ($from && $to) {
            $query .= " AND DATE(created_at) BETWEEN :from AND :to";
            $params[':from'] = $from;
            $params[':to'] = $to;
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /* === UPDATE ROW (ONLY survey_responses) === */
    if ($_GET['ajax'] === 'update' && isset($_POST['id'])) {

        $id = (int)$_POST['id'];
        $client = trim($_POST['client_name'] ?? '');
        $account = trim($_POST['account_number'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $feedback = trim($_POST['feedback'] ?? '');

        $stmt = $conn->prepare("
            UPDATE survey_responses
            SET client_name = :client,
                account_number = :acc,
                district = :district,
                location = :location,
                feedback = :feedback
            WHERE id = :id
        ");

        $ok = $stmt->execute([
            ':client' => $client,
            ':acc' => $account,
            ':district' => $district,
            ':location' => $location,
            ':feedback' => $feedback,
            ':id' => $id
        ]);

        echo $ok ? "ok" : "fail";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Survey & Feedback — SkyTruFiber CSR</title>

<style>
body {
    margin:0;
    font-family:"Segoe UI", sans-serif;
    background:#f0fff0;
    overflow:hidden;
}

/* Sidebar */
#sidebar {
    width:240px;
    background:#009900;
    color:white;
    position:fixed;
    top:0; left:0; bottom:0;
    padding-top:20px;
    transform:translateX(-100%);
    transition:0.3s;
    z-index:10;
}
#sidebar.active {
    transform:translateX(0);
}

#sidebar h2 {
    text-align:center;
    padding:15px;
    background:#007a00;
}
#sidebar h2 img {
    height:40px;
}

#sidebar a {
    display:block;
    padding:15px 20px;
    text-decoration:none;
    color:white;
    font-weight:600;
}
#sidebar a:hover {
    background:#00b300;
}

/* Main content */
#main-content {
    transition:margin-left 0.3s;
}
#main-content.shifted {
    margin-left:240px;
}

/* Header */
header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    background:#00a000;
    padding:10px 20px;
    color:white;
}
header img {
    height:45px;
}
#hamburger {
    font-size:32px;
    cursor:pointer;
    background:none;
    border:none;
    color:white;
}

/* Tabs */
#tabs {
    display:flex;
    gap:10px;
    padding:10px 20px;
    background:#e9ffe9;
}
.tab {
    padding:8px 16px;
    background:#ddd;
    border-radius:6px;
    font-weight:700;
    cursor:pointer;
    color:#005500;
}
.tab.active {
    background:#009900;
    color:white;
}

/* Filters */
#filters {
    background:white;
    padding:15px;
    margin:15px;
    border-radius:8px;
    display:flex;
    gap:15px;
    align-items:center;
    flex-wrap:wrap;
}
#filters input {
    padding:8px;
    border-radius:6px;
    border:1px solid #ccc;
}

/* Table */
table {
    width:95%;
    margin:10px auto;
    border-collapse:collapse;
    background:white;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}
th {
    background:#009900;
    color:white;
    padding:12px;
}
td {
    padding:10px;
    border-bottom:1px solid #eee;
}
tr:hover {
    background:#eaffe8;
}

.edit-btn {
    padding:6px 12px;
    background:#007a00;
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
}
.edit-btn:hover {
    background:#00a000;
}

/* Modal */
#editModal {
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.5);
    display:none;
    justify-content:center;
    align-items:center;
}
.modal-box {
    background:white;
    padding:20px;
    width:90%;
    max-width:400px;
    border-radius:10px;
}
.modal-box input,
.modal-box textarea {
    width:100%;
    padding:10px;
    margin:8px 0;
    border-radius:6px;
    border:1px solid #ccc;
}
.modal-actions {
    text-align:right;
    margin-top:10px;
}
.modal-actions button {
    padding:8px 14px;
    border:none;
    border-radius:6px;
}
#saveBtn {
    background:#009900;
    color:white;
}
#closeBtn {
    background:#bbb;
}
</style>

</head>
<body>

<!-- Sidebar -->
<div id="sidebar">
    <h2><img src="<?= $logoPath ?>"> Menu</h2>
    <a href="csr_dashboard.php">💬 Chat Dashboard</a>
    <a href="csr_dashboard.php?tab=mine">👥 My Clients</a>
    <a href="survey_responses.php" style="background:#00b300;">📝 Surveys & Feedback</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<!-- Main Content -->
<div id="main-content">

<header>
    <button id="hamburger" onclick="toggleSidebar()">☰</button>
    <div>
        <img src="<?= $logoPath ?>">
        Survey & Feedback — <?= htmlspecialchars($csr_fullname) ?>
    </div>
</header>

<div id="tabs">
    <div class="tab" onclick="goTo('csr_dashboard.php')">💬 All Clients</div>
    <div class="tab" onclick="goTo('csr_dashboard.php?tab=mine')">👥 My Clients</div>
    <div class="tab active">📝 Surveys & Feedback</div>
</div>

<div id="filters">
    <input type="text" id="searchBox" placeholder="Search client, account #, location or feedback...">
    <label>From: <input type="date" id="from"></label>
    <label>To: <input type="date" id="to"></label>
    <button class="edit-btn" onclick="loadTable()">Filter</button>
</div>

<div id="table-container"></div>

</div>

<!-- Edit Modal -->
<div id="editModal">
    <div class="modal-box">
        <h3>Edit Survey Response</h3>
        <input type="hidden" id="editId">

        <label>Client Name:</label>
        <input type="text" id="editClient">

        <label>Account Number:</label>
        <input type="text" id="editAccount">

        <label>District:</label>
        <input type="text" id="editDistrict">

        <label>Barangay/Location:</label>
        <input type="text" id="editLocation">

        <label>Feedback:</label>
        <textarea id="editFeedback" rows="5"></textarea>

        <div class="modal-actions">
            <button id="closeBtn" onclick="closeModal()">Cancel</button>
            <button id="saveBtn" onclick="saveChanges()">Save</button>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('main-content').classList.toggle('shifted');
}
function goTo(url) { window.location.href = url; }

function loadTable() {
    const search = document.getElementById('searchBox').value.trim();
    const from = document.getElementById('from').value;
    const to = document.getElementById('to').value;

    fetch(`survey_responses.php?ajax=load&search=${encodeURIComponent(search)}&from=${from}&to=${to}`)
        .then(r => r.json())
        .then(rows => {
            let html = `<table>
                <tr>
                    <th>#</th>
                    <th>Client Name</th>
                    <th>Account #</th>
                    <th>District</th>
                    <th>Location</th>
                    <th>Feedback</th>
                    <th>Date</th>
                    <th>Edit</th>
                </tr>`;

            if (rows.length === 0) {
                html += `<tr><td colspan="8" style="text-align:center;padding:20px;">No records found.</td></tr>`;
            } else {
                rows.forEach((r,i)=>{
                    html += `<tr>
                        <td>${i+1}</td>
                        <td>${escape(r.client_name)}</td>
                        <td>${escape(r.account_number)}</td>
                        <td>${escape(r.district)}</td>
                        <td>${escape(r.location)}</td>
                        <td>${escape(r.feedback)}</td>
                        <td>${new Date(r.created_at).toLocaleString()}</td>
                        <td><button class="edit-btn" onclick="openModal(${r.id}, '${escape(r.client_name)}', '${escape(r.account_number)}', '${escape(r.district)}', '${escape(r.location)}', \`${escape(r.feedback)}\`)">Edit</button></td>
                    </tr>`;
                });
            }

            html += `</table>`;
            document.getElementById('table-container').innerHTML = html;
        });
}

function escape(str) {
    if (!str) return '';
    return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function openModal(id, client, account, district, location, feedback) {
    document.getElementById('editId').value = id;
    document.getElementById('editClient').value = client;
    document.getElementById('editAccount').value = account;
    document.getElementById('editDistrict').value = district;
    document.getElementById('editLocation').value = location;
    document.getElementById('editFeedback').value = feedback.replace(/&quot;/g,'"');
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

function saveChanges() {
    const id = document.getElementById('editId').value;
    const client = document.getElementById('editClient').value;
    const acc = document.getElementById('editAccount').value;
    const district = document.getElementById('editDistrict').value;
    const location = document.getElementById('editLocation').value;
    const feedback = document.getElementById('editFeedback').value;

    fetch('survey_responses.php?ajax=update', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            id, client_name:client, account_number:acc, district, location, feedback
        })
    }).then(r => r.text()).then(resp => {
        if (resp === 'ok') {
            alert('✅ Updated successfully!');
            closeModal();
            loadTable();
        } else {
            alert('❌ Update failed!');
        }
    });
}

document.getElementById('searchBox').addEventListener('keyup', loadTable);
window.onload = loadTable;
setInterval(loadTable, 10000);
</script>

</body>
</html>
