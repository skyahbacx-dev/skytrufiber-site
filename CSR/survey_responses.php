<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

// Logo used for PDF
$logoPath = file_exists("AHBALOGO.png") ? "AHBALOGO.png" : "../SKYTRUFIBER/AHBALOGO.png";

// Pagination settings
$limit = 10;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : "created_at";
$dir  = isset($_GET['dir']) && strtolower($_GET['dir']) === "asc" ? "ASC" : "DESC";

// Accepted columns (prevent SQL injection)
$allowedSort = ["client_name", "account_number", "district", "location", "feedback", "created_at"];
if (!in_array($sort, $allowedSort)) $sort = "created_at";

// Search SQL
$cond = "";
$params = [];

if ($search !== "") {
    $cond = "WHERE client_name ILIKE :s OR account_number ILIKE :s OR district ILIKE :s OR location ILIKE :s OR email ILIKE :s";
    $params[':s'] = "%$search%";
}

// Count rows
$countQuery = $conn->prepare("SELECT COUNT(*) FROM survey_responses $cond");
$countQuery->execute($params);
$totalRows = $countQuery->fetchColumn();
$totalPages = max(1, ceil($totalRows / $limit));

// Get paginated data
$query = $conn->prepare("
    SELECT id, client_name, account_number, district, location, email, feedback, created_at
    FROM survey_responses
    $cond
    ORDER BY $sort $dir
    LIMIT $limit OFFSET $offset
");
$query->execute($params);
$rows = $query->fetchAll(PDO::FETCH_ASSOC);

// CSV export
if (isset($_GET['export']) && $_GET['export'] === "csv") {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=survey_responses.csv");

    $out = fopen("php://output", "w");
    fputcsv($out, ["ID", "Client Name", "Account Number", "District", "Location", "Email", "Feedback", "Date Installed"]);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['client_name'],
            $r['account_number'],
            $r['district'],
            $r['location'],
            $r['email'],
            $r['feedback'],
            date('Y-m-d', strtotime($r['created_at']))
        ]);
    }
    fclose($out);
    exit;
}

// PDF export (fallback)
if (isset($_GET['export']) && $_GET['export'] === "pdf") {
    header("Content-Type: application/pdf");
    header("Content-Disposition: attachment; filename=survey_responses.pdf");

    echo "%PDF-1.4\n";
    echo "Survey Responses Export\n";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Survey Responses</title>
<link rel="stylesheet" href="../css/csr_styles.css">
<style>
/* ================== GLOBAL ================== */
:root{
  --green:#0aa05b;
  --green-600:#07804a;
  --green-700:#056b3d;
  --soft:#eefcf4;
  --light:#f6fff9;
  --bg:#ffffff;
  --line:#e7efe9;
  --csr:#e6f2ff;
  --client:#ecfff1;
  --text:#142015;
  --shadow:0 8px 24px rgba(0,0,0,.08);
}
*{box-sizing:border-box}
body{margin:0;font-family:Segoe UI,Arial,sans-serif;background:var(--light);color:var(--text);overflow:hidden}

/* ================== HEADER ================== */
header{
  height:64px;
  background:linear-gradient(135deg,#0fb572,#0aa05b);
  color:#fff;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 16px;
  box-shadow:var(--shadow);
}
#hamb{
  background:none;
  border:none;
  color:#fff;
  font-size:26px;
  cursor:pointer;
}
.brand{
  display:flex;
  align-items:center;
  gap:10px;
}
.brand img{height:40px;border-radius:10px}

/* ================== SIDEBAR ================== */
#overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.4);
  display:none;
  z-index:8;
}
#sidebar{
  position:fixed;
  top:0;
  left:0;
  width:260px;
  height:100vh;
  background:var(--green-600);
  color:#fff;
  transform:translateX(-100%);
  transition:.25s;
  z-index:9;
  box-shadow:var(--shadow);
}
#sidebar.active{transform:translateX(0)}
#sidebar h2{
  margin:0;
  padding:20px;
  background:var(--green-700);
  text-align:center;
}
#sidebar a{
  display:block;
  padding:16px 18px;
  text-decoration:none;
  color:#fff;
  font-weight:600;
}
#sidebar a:hover{background:#12c474}

/* ================== TABS ================== */
.tabs{
  display:flex;
  gap:10px;
  padding:10px 16px;
  background:var(--soft);
  border-bottom:1px solid var(--line);
}
.tab{
  padding:10px 16px;
  border-radius:999px;
  background:#fff;
  border:1px solid var(--line);
  font-weight:700;
  color:var(--green-600);
  cursor:pointer;
}
.tab.active{
  background:var(--green-600);
  color:#fff;
  border-color:var(--green-600);
}

/* ================== MAIN LAYOUT ================== */
#main{
  display:grid;
  grid-template-columns:340px 1fr;
  height:calc(100vh - 112px);
}

/* ================== LEFT COLUMN ================== */
#client-col{
  background:var(--bg);
  border-right:1px solid var(--line);
  overflow:auto;
}
.client-item{
  display:flex;
  justify-content:space-between;
  padding:12px;
  margin:12px;
  background:#fff;
  border:1px solid var(--line);
  border-radius:14px;
  cursor:pointer;
}
.client-item:hover{background:#f7fffb}
.client-meta{font-size:13px}
.client-name{font-weight:800}
.client-actions{}

.pill{border:none;border-radius:999px;padding:6px 11px;color:#fff;font-weight:600;cursor:pointer}
.green{background:#19b66e}
.red{background:#e66a6a}
.gray{background:#999}

/* ================== RIGHT COLUMN ================== */
#chat-col{
  position:relative;
  background:#fff;
  display:flex;
  flex-direction:column;
}
#collapseBtn{
  position:absolute;
  top:12px;
  right:12px;
  width:40px;
  height:40px;
  border-radius:50%;
  border:1px solid var(--line);
  background:#fff;
  font-size:20px;
  cursor:pointer;
  z-index:5;
}
#chat-col.collapsed{
  flex:0 !important;
  width:40px !important;
  min-width:40px;
  overflow:hidden;
}

/* ================== CHAT HEADER ================== */
#chat-head{
  background:linear-gradient(135deg,#0aa05b,#07804a);
  color:#fff;
  padding:12px 16px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  font-weight:700;
}
.chat-title{
  display:flex;
  gap:12px;
  align-items:center;
}
.avatar{
  width:36px;
  height:36px;
  border-radius:50%;
  background:#eaf7ef;
  border:2px solid rgba(255,255,255,.4);
  display:flex;
  align-items:center;
  justify-content:center;
}
.avatar img{width:100%;height:100%;object-fit:cover}
.info-dot{
  width:28px;
  height:28px;
  border-radius:50%;
  background:rgba(255,255,255,.2);
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:900;
}

/* ================== CHAT MESSAGES ================== */
#messages{
  flex:1;
  padding:20px;
  overflow:auto;
  position:relative;
}
.msg{
  clear:both;
  margin:8px 0;
  max-width:70%;
}
.msg .bubble{
  padding:12px;
  border-radius:16px;
  box-shadow:0 2px 8px rgba(0,0,0,.08);
}
.msg.client{float:left}
.msg.client .bubble{
  background:var(--client);
}
.msg.csr{float:right}
.msg.csr .bubble{
  background:var(--csr);
}
.meta{font-size:11px;color:#666;margin-top:6px}

/* ================== CHAT INPUT ================== */
#input{
  display:flex;
  padding:10px;
  border-top:1px solid var(--line);
  gap:10px;
}
#msg{
  flex:1;
  padding:12px;
  border-radius:12px;
  border:1px solid #ccc;
}
#input button{
  padding:12px 20px;
  background:var(--green);
  border:none;
  border-radius:12px;
  color:#fff;
  font-weight:700;
}

/* ================== REMINDERS ================== */
#reminders{display:none;flex-direction:column}
#rem-list{overflow:auto;padding:10px}
.card{
  background:#fff;
  padding:12px;
  border:1px solid var(--line);
  border-radius:12px;
  margin-bottom:10px;
}
.badge{
  display:inline-block;
  padding:4px 8px;
  border-radius:999px;
  font-size:11px;
  color:#fff;
  margin-top:6px;
  margin-right:6px;
}
.badge.upcoming{background:#ff9800}
.badge.due{background:#e91e63}
.badge.sent{background:#2196f3}

/* ================== RESPONSIVE ================== */
@media (max-width:980px){
  #main{grid-template-columns:1fr}
  #client-col{height:40vh}
  #chat-col{height:60vh}
}
</style>
</head>

<body>

<!-- HEADER / NAV BAR -->
<header class="csr-header">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="brand">
        <img src="<?= $logoPath ?>" alt="Logo">
        <span>CSR Dashboard — Survey Responses</span>
    </div>
</header>

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


<!-- TABS -->
<div class="tabs">
    <div class="tab" onclick="location.href='csr_dashboard.php'">💬 All Clients</div>
    <div class="tab" onclick="location.href='csr_dashboard.php?tab=mine'">👤 My Clients</div>
    <div class="tab" onclick="location.href='csr_dashboard.php?tab=rem'">⏰ Reminders</div>
    <div class="tab active">📝 Survey Responses</div>
    <div class="tab" onclick="location.href='update_profile.php'">👤 Edit Profile</div>
</div>

<!-- CONTENT -->
<div style="padding:20px;">

    <h2>Survey Responses</h2>

    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search ?? "", ENT_QUOTES) ?>">
        <button class="btn" type="submit">Search</button>
        <button class="btn-csv" type="submit" name="export" value="csv">Export CSV</button>
        <button class="btn-pdf" type="submit" name="export" value="pdf">Export PDF</button>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th onclick="sortBy('id')">ID</th>
                    <th onclick="sortBy('client_name')">Client Name</th>
                    <th onclick="sortBy('account_number')">Account Number</th>
                    <th onclick="sortBy('district')">District</th>
                    <th onclick="sortBy('location')">Location</th>
                    <th onclick="sortBy('email')">Email</th>
                    <th onclick="sortBy('feedback')">Feedback</th>
                    <th onclick="sortBy('created_at')">Date Installed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['id']) ?></td>
                        <td><?= htmlspecialchars($r['client_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['account_number'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['district'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['location'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['feedback'] ?? '') ?></td>
                        <td><?= date('Y-m-d', strtotime($r['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="pagination">
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&dir=<?= $dir ?>" 
               class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>

</div>

<script>
function sortBy(column) {
    const url = new URL(window.location.href);
    url.searchParams.set("sort", column);

    let currentDir = url.searchParams.get("dir") || "DESC";
    url.searchParams.set("dir", currentDir === "DESC" ? "ASC" : "DESC");

    window.location.href = url.toString();
}
</script>

</body>
</html>
