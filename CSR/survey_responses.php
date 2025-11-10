<?php
session_start();
include '../db_connect.php';

// Validate CSR login
if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$csr_user = $_SESSION['csr_user'];

// Get CSR full name
$stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username=:u LIMIT 1");
$stmt->execute([':u'=>$csr_user]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$csr_fullname = $row['full_name'] ?? $csr_user;

// Logo location
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';


/* ================================================================
   AJAX CONTROLLER
   ================================================================ */
if (isset($_GET['ajax'])) {

    /* ====================================
       🚀 LOAD DATA (Filters + Sorting + Pagination)
       ==================================== */
    if ($_GET['ajax'] === "load") {

        $search = "%".($_GET['search'] ?? "")."%";
        $from = $_GET['from'] ?? "";
        $to = $_GET['to'] ?? "";
        $month = $_GET['month'] ?? "";

        // Allowed sorting fields
        $allowedSort = ["client_name","district","created_at"];
        $sort = in_array($_GET['sort'] ?? "created_at",$allowedSort) ? $_GET['sort'] : "created_at";
        $order = ($_GET['order'] ?? "DESC") === "ASC" ? "ASC" : "DESC";

        // Pagination
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Base parameters
        $params = [":s"=>$search];

        // Base query (used for count + select)
        $baseQuery = "
            SELECT 
                id, client_name, account_number, district, location, email,
                feedback AS remarks, created_at
            FROM survey_responses
            WHERE (
                client_name ILIKE :s OR
                account_number ILIKE :s OR
                district ILIKE :s OR
                location ILIKE :s OR
                feedback ILIKE :s OR
                email ILIKE :s
            )
        ";

        // Date range
        if ($from && $to) {
            $baseQuery .= " AND DATE(created_at) BETWEEN :f AND :t";
            $params[":f"] = $from;
            $params[":t"] = $to;
        }

        // Month filter
        if ($month) {
            $baseQuery .= " AND EXTRACT(MONTH FROM created_at) = :m";
            $params[":m"] = intval($month);
        }

        // Count total
        $countStmt = $conn->prepare("SELECT COUNT(*) FROM ($baseQuery) AS count_table");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Apply sorting + pagination
        $query = $baseQuery . " ORDER BY $sort $order LIMIT :limit OFFSET :offset";

        $stmt = $conn->prepare($query);

        // Bind parameters
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "rows" => $rows,
            "total" => $total,
            "page" => $page,
            "limit" => $limit
        ]);
        exit;
    }

    /* ====================================
       ✅ UPDATE DATA
       ==================================== */
    if ($_GET['ajax'] === "update") {

        $id = (int)$_POST['id'];

        $stmt = $conn->prepare("
            UPDATE survey_responses
            SET client_name = :c,
                account_number = :a,
                district = :d,
                location = :l,
                email = :e,
                feedback = :f
            WHERE id=:id
        ");

        $ok = $stmt->execute([
            ":c" => trim($_POST['client_name']),
            ":a" => trim($_POST['account_number']),
            ":d" => trim($_POST['district']),
            ":l" => trim($_POST['location']),
            ":e" => trim($_POST['email']),
            ":f" => trim($_POST['feedback']),
            ":id"=> $id
        ]);

        echo $ok ? "ok" : "fail";
        exit;
    }

    http_response_code(400);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Survey & Feedback — <?= htmlspecialchars($csr_fullname) ?></title>

<style>
body {
    margin:0;
    font-family:Segoe UI, Arial, sans-serif;
    background:#f6fff6;
    overflow:hidden;
}

/* Sidebar */
#sidebar {
    width:260px;
    position:fixed;
    left:-260px;
    top:0;
    height:100vh;
    background:#006b00;
    color:white;
    transition:0.3s;
    z-index:1000;
}
#sidebar.active { left:0; }
#sidebar h2 {
    margin:0;
    padding:18px;
    background:#004f00;
    text-align:center;
}
#sidebar a {
    display:block;
    padding:14px 18px;
    text-decoration:none;
    color:white;
    font-weight:600;
}
#sidebar a:hover { background:#00b300; }

/* Header */
header {
    height:60px;
    background:#009900;
    color:white;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 20px;
    font-size:18px;
    font-weight:700;
}
#hamburger {
    cursor:pointer;
    font-size:28px;
}

/* Tabs */
#tabs {
    display:flex;
    gap:10px;
    padding:12px 20px;
    background:#eaffea;
    border-bottom:1px solid #cce5cc;
}
.tab {
    padding:10px 18px;
    border-radius:6px;
    cursor:pointer;
    font-weight:600;
    color:#006b00;
}
.tab.active {
    background:#006b00;
    color:white;
}

/* Main */
#main {
    padding:20px;
    overflow-y:auto;
    height:calc(100vh - 120px);
}

/* Table */
table {
    width:100%;
    border-collapse:collapse;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}
th {
    background:#007e00;
    color:white;
    cursor:pointer;
}
th, td {
    padding:10px;
    border-bottom:1px solid #ddd;
}
tr:hover { background:#eefbee; }

/* Pagination */
#pagination button {
    padding:6px 10px;
    border:none;
    background:#009900;
    color:white;
    border-radius:6px;
    cursor:pointer;
}
#pagination button:hover { background:#007700; }

/* Modal */
#editModal {
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.4);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:3000;
}
.modal-box {
    background:white;
    padding:20px;
    width:90%;
    max-width:450px;
    border-radius:10px;
}
.modal-box input, 
.modal-box textarea {
    width:100%;
    padding:10px;
    margin:6px 0;
    border:1px solid #ccc;
}
</style>
</head>

<body>

<div id="sidebar">
    <h2>Menu</h2>
    <a href="csr_dashboard.php">💬 All Clients</a>
    <a href="csr_dashboard.php?tab=mine">👤 My Clients</a>
    <a href="csr_dashboard.php?tab=rem">⏰ Reminders</a>
    <a style="background:#00b300;">📝 Survey & Feedback</a>
    <a href="update_profile.php">👤 Edit Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<header>
    <button id="hamburger" onclick="toggleSidebar()">☰</button>
    Survey & Feedback — <?= htmlspecialchars($csr_fullname) ?>
</header>

<div id="tabs">
    <div class="tab" onclick="location.href='csr_dashboard.php'">💬 All Clients</div>
    <div class="tab" onclick="location.href='csr_dashboard.php?tab=mine'">👤 My Clients</div>
    <div class="tab" onclick="location.href='csr_dashboard.php?tab=rem'">⏰ Reminders</div>
    <div class="tab active">📝 Survey & Feedback</div>
</div>

<div id="main">

    <!-- Filters -->
    <div style="display:flex;gap:10px;align-items:center;margin-bottom:10px;">
        <label>Search:</label>
        <input id="searchBox" type="text" placeholder="Search..." style="padding:8px;border:1px solid #ccc;border-radius:6px;">

        <label>From:</label>
        <input id="fromDate" type="date">

        <label>To:</label>
        <input id="toDate" type="date">

        <label>Month:</label>
        <select id="monthFilter">
            <option value="">All</option>
            <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>"><?= date("F", mktime(0,0,0,$m,1)) ?></option>
            <?php endfor; ?>
        </select>

        <button onclick="loadTable()" style="background:#009900;color:white;border:none;border-radius:6px;padding:8px 12px;">Filter</button>

        <!-- EXPORTS -->
        <button onclick="exportCSV()" style="background:#0055cc;color:white;border:none;border-radius:6px;padding:8px 12px;">CSV</button>
        <button onclick="exportExcel()" style="background:#003399;color:white;border:none;border-radius:6px;padding:8px 12px;">Excel</button>
        <button onclick="exportPDF()" style="background:#aa0000;color:white;border:none;border-radius:6px;padding:8px 12px;">📄 PDF</button>
        <button onclick="printView()" style="background:#444;color:white;border:none;border-radius:6px;padding:8px 12px;">Print</button>
    </div>

    <div id="table-container"></div>
    <div id="pagination" style="margin-top:10px;text-align:center;"></div>
</div>

<!-- Edit Modal -->
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
        <textarea id="editFeedback"></textarea>

        <div style="text-align:right;margin-top:10px;">
            <button onclick="closeModal()" style="background:#ccc;border:none;padding:8px 12px;">Cancel</button>
            <button onclick="saveChanges()" style="background:#009900;color:white;border:none;padding:8px 12px;">Save</button>
        </div>
    </div>
</div>


<script>
let currentPage = 1;
let currentSort = "created_at";
let currentOrder = "DESC";

function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("active");
}

function safe(t){ return (t ?? "").replace(/"/g,"&quot;").replace(/'/g,"&#39;"); }

function loadTable(page=1){
    currentPage = page;

    const s = document.getElementById("searchBox").value.trim();
    const f = document.getElementById("fromDate").value;
    const t = document.getElementById("toDate").value;
    const m = document.getElementById("monthFilter").value;

    fetch(`survey_responses.php?ajax=load&search=${encodeURIComponent(s)}&from=${f}&to=${t}&month=${m}&page=${page}&sort=${currentSort}&order=${currentOrder}`)
        .then(r=>r.json())
        .then(data=>{
            const rows = data.rows;
            const total = data.total;
            const limit = data.limit;
            const page = data.page;

            let html = "<table><thead><tr>";
            html += `<th onclick="sortBy('client_name')">Client Name</th>`;
            html += `<th>Account Number</th>`;
            html += `<th onclick="sortBy('district')">District</th>`;
            html += `<th>Location</th>`;
            html += `<th>Email</th>`;
            html += `<th>Feedback</th>`;
            html += `<th onclick="sortBy('created_at')">Date Installed</th>`;
            html += `<th>Action</th>`;
            html += "</tr></thead><tbody>";

            if (!rows.length) {
                html += "<tr><td colspan='8' style='text-align:center;padding:20px;color:#777;'>No records found.</td></tr>";
            } else {
                rows.forEach(r=>{
                    html += `
                    <tr>
                        <td>${r.client_name}</td>
                        <td>${r.account_number || ""}</td>
                        <td>${r.district || ""}</td>
                        <td>${r.location || ""}</td>
                        <td>${r.email || ""}</td>
                        <td>${r.remarks || ""}</td>
                        <td>${new Date(r.created_at).toLocaleString()}</td>
                        <td><button onclick="openEdit(${r.id},'${safe(r.client_name)}','${safe(r.account_number)}','${safe(r.district)}','${safe(r.location)}','${safe(r.email)}','${safe(r.remarks)}')" style="background:#009900;color:white;border:none;padding:6px 10px;border-radius:6px;">Edit</button></td>
                    </tr>
                    `;
                });
            }

            html += "</tbody></table>";
            document.getElementById("table-container").innerHTML = html;

            let totalPages = Math.ceil(total / limit);
            let pag = "";

            if (totalPages > 1) {
                if (page > 1) {
                    pag += `<button onclick="loadTable(${page-1})">⬅ Prev</button> `;
                }
                pag += ` Page ${page} of ${totalPages} `;
                if (page < totalPages) {
                    pag += `<button onclick="loadTable(${page+1})">Next ➡</button>`;
                }
            }
            document.getElementById("pagination").innerHTML = pag;
        });
}

function sortBy(col){
    if (currentSort === col) {
        currentOrder = (currentOrder === "ASC") ? "DESC" : "ASC";
    } else {
        currentSort = col;
        currentOrder = "ASC";
    }
    loadTable(1);
}

function openEdit(id,client,account,district,location,email,feedback){
    document.getElementById("editId").value = id;
    document.getElementById("editClient").value = client;
    document.getElementById("editAccount").value = account;
    document.getElementById("editDistrict").value = district;
    document.getElementById("editLocation").value = location;
    document.getElementById("editEmail").value = email;
    document.getElementById("editFeedback").value = feedback;
    document.getElementById("editModal").style.display = "flex";
}

function closeModal(){
    document.getElementById("editModal").style.display = "none";
}

function saveChanges(){
    const data = new URLSearchParams({
        id: document.getElementById("editId").value,
        client_name: document.getElementById("editClient").value,
        account_number: document.getElementById("editAccount").value,
        district: document.getElementById("editDistrict").value,
        location: document.getElementById("editLocation").value,
        email: document.getElementById("editEmail").value,
        feedback: document.getElementById("editFeedback").value
    });

    fetch("survey_responses.php?ajax=update",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:data.toString()
    })
    .then(r=>r.text())
    .then(resp=>{
        if (resp === "ok") {
            alert("✅ Updated successfully!");
            closeModal();
            loadTable(currentPage);
        } else {
            alert("❌ Error updating record.");
        }
    });
}

function exportCSV(){
    const s=document.getElementById('searchBox').value.trim();
    const f=document.getElementById('fromDate').value;
    const t=document.getElementById('toDate').value;
    const m=document.getElementById('monthFilter').value;
    window.location = `export_csv.php?search=${encodeURIComponent(s)}&from=${f}&to=${t}&month=${m}`;
}

function exportExcel(){
    const s=document.getElementById('searchBox').value.trim();
    const f=document.getElementById('fromDate').value;
    const t=document.getElementById('toDate').value;
    const m=document.getElementById('monthFilter').value;
    window.location = `export_excel.php?search=${encodeURIComponent(s)}&from=${f}&to=${t}&month=${m}`;
}

function exportPDF(){
    const s=document.getElementById('searchBox').value.trim();
    const f=document.getElementById('fromDate').value;
    const t=document.getElementById('toDate').value;
    const m=document.getElementById('monthFilter').value;

    window.open(
        `export_pdf.php?search=${encodeURIComponent(s)}&from=${f}&to=${t}&month=${m}`,
        "_blank"
    );
}

function printView(){
    const html = document.getElementById("table-container").innerHTML;

    const w = window.open("", "_blank");
    w.document.write(`
        <html>
        <head>
            <title>Survey Report</title>
            <style>
                body { font-family:Segoe UI, Arial, sans-serif; }
                table { width:100%; border-collapse:collapse; }
                th, td { border:1px solid #000; padding:8px; }
            </style>
        </head>
        <body>
            <img src="<?= $logoPath ?>" style="height:60px;margin-bottom:20px;">
            ${html}
        </body>
        </html>
    `);
    w.document.close();
    w.print();
}

window.onload = loadTable;
</script>

</body>
</html>
