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
/* Extra table styling */
.table-container {
    width: 100%;
    overflow-x: auto;
    background: #ffffff;
    padding: 20px;
    border-radius: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
}

th {
    padding: 10px;
    background: #0aa05b;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

td {
    padding: 10px;
    border-bottom: 1px solid #e4e4e4;
}

tr:hover {
    background: #f8fff9;
}
.search-box {
    margin: 15px 0;
}
.btn {
    padding: 8px 14px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    margin-right: 5px;
}

.btn-csv {
    background: #0aa05b;
    color: #fff;
}

.btn-pdf {
    background: #ff5252;
    color: #fff;
}

.pagination {
    margin-top: 15px;
}

.pagination a {
    display: inline-block;
    padding: 8px 12px;
    margin: 2px;
    border: 1px solid #0aa05b;
    color: #0aa05b;
    border-radius: 6px;
    text-decoration: none;
}

.pagination a.active {
    background: #0aa05b;
    color: white;
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

<!-- SIDEBAR -->
<?php include "sidebar.php"; ?>

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
