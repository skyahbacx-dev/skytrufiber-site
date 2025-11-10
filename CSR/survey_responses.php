<?php
session_start();
include '../db_connect.php';

// Validate CSR
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

// Logo path
$logoPath = file_exists('AHBALOGO.png') ? 'AHBALOGO.png' : '../SKYTRUFIBER/AHBALOGO.png';

/* ============================================================
   AJAX HANDLERS
   ============================================================ */
if (isset($_GET['ajax'])) {

    /* ✅ LOAD DATA WITH PAGINATION + SORTING */
    if ($_GET['ajax'] === "load") {

        $search = "%".($_GET['search'] ?? "")."%";
        $from = $_GET['from'] ?? "";
        $to = $_GET['to'] ?? "";
        $month = $_GET['month'] ?? "";
        $sort = $_GET['sort'] ?? "created_at";
        $order = $_GET['order'] ?? "DESC";

        $allowedSort = ["created_at","client_name","district"]; 
        if (!in_array($sort,$allowedSort)) $sort="created_at";
        $order = ($order === "ASC") ? "ASC" : "DESC";

        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $params = [":s"=>$search];

        $query = "
        SELECT id, client_name, account_number, district, location, email, feedback AS remarks, created_at 
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

        if ($from && $to) {
            $query .= " AND DATE(created_at) BETWEEN :f AND :t";
            $params[":f"] = $from;
            $params[":t"] = $to;
        }

        if ($month) {
            $query .= " AND EXTRACT(MONTH FROM created_at) = :m";
            $params[":m"] = intval($month);
        }

        $countQuery = "SELECT COUNT(*) FROM ($query) AS x";

        $query .= " ORDER BY {$sort} {$order} LIMIT $limit OFFSET $offset";

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        echo json_encode([
            "rows" => $rows,
            "total" => $total,
            "page" => $page,
            "limit" => $limit
        ]);
        exit;
    }

    /* ✅ UPDATE RECORD */
    if ($_GET['ajax'] === "update") {
        $id = (int)$_POST['id'];

        $stmt = $conn->prepare("
        UPDATE survey_responses
        SET client_name=:c, account_number=:a, district=:d, location=:l, email=:e, feedback=:f
        WHERE id=:id
        ");
        $ok = $stmt->execute([
            ':c'=>trim($_POST['client_name']),
            ':a'=>trim($_POST['account_number']),
            ':d'=>trim($_POST['district']),
            ':l'=>trim($_POST['location']),
            ':e'=>trim($_POST['email']),
            ':f'=>trim($_POST['feedback']),
            ':id'=>$id
        ]);
        echo $ok ? "ok" : "fail";
        exit;
    }

    http_response_code(400);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Survey & Feedback — <?= htmlspecialchars($csr_fullname) ?></title>

<style>
body {
    margin:0;
    font-family:Segoe UI, Arial, sans-serif;
    background:#f2fff2;
    overflow:hidden;
}
header {
    background:#009900;
    color:white;
    padding:12px 18px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
#hamburger {
    cursor:pointer;
    font-size:26px;
    background:none;
    border:none;
    color:white;
}
#sidebar {
    width:260px;
    background:#006b00;
    color:white;
    position:fixed;
    left:-260px;
    top:0;
    height:100vh;
    transition:0.3s;
    z-index:999;
}
#sidebar.active { left:0; }
#sidebar h2 {
    margin:0;
    padding:18px;
    text-align:center;
    background:#004f00;
}
#sidebar a {
    padding:14px 20px;
    display:block;
    color:white;
    text-decoration:none;
    font-weight:600;
}
#sidebar a:hover { background:#00b300; }

#tabs {
    display:flex;
    gap:8px;
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
.tab.active { background:#006b00; color:white; }

#main {
    height:calc(100vh - 120px);
    padding:20px;
    overflow-y:auto;
}
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
    display:none;
    background:rgba(0,0,0,0.4);
    justify-content:center;
    align-items:center;
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
}
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
    <a href="update_profile.php">👤 Edit Profile</a>
    <a href="csr_logout.php">🚪 Logout</a>
</div>

<header>
    <button id="hamburger" onclick="toggleSidebar()">☰</button>
    <div>Survey & Feedback — <?= htmlspecialchars($csr_fullname) ?></div>
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
                <option value="<?= $m ?>"><?= date("F",mktime(0,0,0,$m,1)) ?></option>
            <?php endfor; ?>
        </select>

        <button onclick="loadTable()" style="background:#009900;color:white;border:none;border-radius:6px;padding:8px 12px;">Filter</button>

        <!-- Export Buttons -->
        <button onclick="exportCSV()" style="background:#0055cc;color:white;border:none;border-radius:6px;padding:8px 12px;">CSV</button>

        <button onclick="exportExcel()" style="background:#003399;color:white;border:none;border-radius:6px;padding:8px 12px;">Excel</button>

        <button onclick="window.open('export_pdf.php?search='+search+'&from='+from+'&to='+to+'&month='+month)">📄 PDF</button>

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
    document.getElementById('sidebar').classList.toggle('active');
}

function safe(t){ return (t??"").replace(/"/g,"&quot;").replace(/'/g,"&#39;"); }

function loadTable(page = 1){
    currentPage = page;

    const q = document.getElementById('searchBox').value.trim();
    const f = document.getElementById('fromDate').value;
    const t = document.getElementById('toDate').value;
    const m = document.getElementById('monthFilter').value;

    fetch(`survey_responses.php?ajax=load&search=${encodeURIComponent(q)}&from=${f}&to=${t}&month=${m}&page=${page}&sort=${currentSort}&order=${currentOrder}`)
        .then(r=>r.json())
        .then(data=>{
            const rows=data.rows;
            const total=data.total;
            const limit=data.limit;
            const page=data.page;

            let html="<table><thead><tr>";
            html+=`<th onclick="sortBy('client_name')">Client</th>`;
            html+=`<th onclick="sortBy('account_number')">Account</th>`;
            html+=`<th onclick="sortBy('district')">District</th>`;
            html+=`<th onclick="sortBy('location')">Location</th>`;
            html+=`<th>Email</th>`;
            html+=`<th>Feedback</th>`;
            html+=`<th onclick="sortBy('created_at')">Date Installed</th>`;
            html+=`<th>Action</th>`;
            html+="</tr></thead><tbody>";

            if(!rows.length){
                html+="<tr><td colspan='8' style='text-align:center;padding:20px;color:#777;'>No records found.</td></tr>";
            }else{
                rows.forEach(r=>{
                    html+=`<tr>
                        <td>${r.client_name}</td>
                        <td>${r.account_number || ""}</td>
                        <td>${r.district || ""}</td>
                        <td>${r.location || ""}</td>
                        <td>${r.email || ""}</td>
                        <td>${r.remarks || ""}</td>
                        <td>${new Date(r.created_at).toLocaleString()}</td>
                        <td>
                            <button onclick="openEdit(${r.id},'${safe(r.client_name)}','${safe(r.account_number)}','${safe(r.district)}','${safe(r.location)}','${safe(r.email)}','${safe(r.remarks)}')" style="background:#009900;color:white;border:none;padding:6px 10px;border-radius:6px;">Edit</button>
                        </td>
                    </tr>`;
                });
            }

            html+="</tbody></table>";

            document.getElementById('table-container').innerHTML = html;

            // Pagination
            let totalPages = Math.ceil(total / limit);
            let pag = "";
            if(totalPages > 1){
                if(page > 1){
                    pag += `<button onclick="loadTable(${page-1})">⬅ Prev</button> `;
                }
                pag += ` Page ${page} of ${totalPages} `;
                if(page < totalPages){
                    pag += `<button onclick="loadTable(${page+1})">Next ➡</button>`;
                }
            }
            document.getElementById('pagination').innerHTML = pag;
        });
}

function sortBy(col){
    if(currentSort === col){
        currentOrder = (currentOrder === "ASC") ? "DESC" : "ASC";
    } else {
        currentSort = col;
        currentOrder = "ASC";
    }
    loadTable(1);
}

// Edit Modal
function openEdit(id,client,account,district,location,email,feedback){
    document.getElementById('editId').value=id;
    document.getElementById('editClient').value=client;
    document.getElementById('editAccount').value=account;
    document.getElementById('editDistrict').value=district;
    document.getElementById('editLocation').value=location;
    document.getElementById('editEmail').value=email;
    document.getElementById('editFeedback').value=feedback;
    document.getElementById('editModal').style.display="flex";
}
function closeModal(){ document.getElementById('editModal').style.display="none"; }

function saveChanges(){
    const id=document.getElementById('editId').value;
    const data=new URLSearchParams({
        id: id,
        client_name: document.getElementById('editClient').value,
        account_number: document.getElementById('editAccount').value,
        district: document.getElementById('editDistrict').value,
        location: document.getElementById('editLocation').value,
        email: document.getElementById('editEmail').value,
        feedback: document.getElementById('editFeedback').value
    });

    fetch("survey_responses.php?ajax=update",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:data.toString()
    })
    .then(r=>r.text())
    .then(resp=>{
        if(resp==="ok"){
            alert("✅ Updated!");
            closeModal();
            loadTable(currentPage);
        } else {
            alert("❌ Update failed.");
        }
    });
}

function exportCSV(){
    const s=document.getElementById('searchBox').value.trim();
    const f=document.getElementById('fromDate').value;
    const t=document.getElementById('toDate').value;
    const m=document.getElementById('monthFilter').value;
    window.location=`export_csv.php?search=${encodeURIComponent(s)}&from=${f}&to=${t}&month=${m}`;
}
function exportExcel(){
    const s=document.getElementById('searchBox').value.trim();
    const f=document.getElementById('fromDate').value;
    const t=document.getElementById('toDate').value;
    const m=document.getElementById('monthFilter').value;
    window.location=`export_excel.php?search=${encodeURIComponent(s)}&from=${f}&to=${t}&month=${m}`;
}
function exportPDF(){
    const s=document.getElementById('searchBox').value.trim();
    const f=document.getElementById('fromDate').value;
    const t=document.getElementById('toDate').value;
    const m=document.getElementById('monthFilter').value;
    window.location=`export_pdf.php?search=${encodeURIComponent(s)}&from=${f}&to=${t}&month=${m}`;
}
function printView(){
    const html=document.getElementById('table-container').innerHTML;
    const printWindow=window.open("","_blank");
    printWindow.document.write("<html><head><title>Survey Report</title></head><body>");
    printWindow.document.write(html);
    printWindow.document.write("</body></html>");
    printWindow.document.close();
    printWindow.print();
}

window.onload=loadTable;
</script>
</body>
</html>
