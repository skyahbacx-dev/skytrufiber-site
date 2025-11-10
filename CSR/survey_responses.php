<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION['csr_user'])) {
    header("Location: csr_login.php");
    exit;
}

$search = trim($_GET['search'] ?? "");
$sort = $_GET['sort'] ?? "id";
$order = ($_GET['order'] ?? "ASC") === "DESC" ? "DESC" : "ASC";

$perPage = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Build search filter
$filter = "";
if ($search !== "") {
    $filter = "WHERE full_name ILIKE :s OR email ILIKE :s OR account_number ILIKE :s";
}

// Fetch total rows
$tc = $conn->prepare("SELECT COUNT(*) FROM survey_responses $filter");
if ($filter) $tc->execute([':s'=>"%$search%"]);
else $tc->execute();
$total = $tc->fetchColumn();
$pages = ceil($total / $perPage);

// Fetch paginated survey data
$q = $conn->prepare("
    SELECT *
    FROM survey_responses
    $filter
    ORDER BY $sort $order
    LIMIT :pp OFFSET :off
");

if ($filter) $q->bindValue(":s", "%$search%", PDO::PARAM_STR);
$q->bindValue(":pp", $perPage, PDO::PARAM_INT);
$q->bindValue(":off", $offset, PDO::PARAM_INT);

$q->execute();
$data = $q->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Survey Responses</title>
<style>
body { font-family: Arial; background: #f2f2f2; margin: 0; padding: 20px; }
.container { background: #fff; padding: 20px; border-radius: 12px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { padding: 8px 10px; border-bottom: 1px solid #ccc; }
th { background: #0aa05b; color: #fff; cursor: pointer; }
tr:hover { background: #f9f9f9; }
input.search { padding: 8px; width: 300px; border-radius: 8px; border: 1px solid #ccc; }
button { padding: 8px 12px; border-radius: 8px; border: none; cursor: pointer; margin-right: 5px; }
.export-btn { background: #1976d2; color: #fff; }
.print-btn { background: #444; color: #fff; }
.pagination a {
    padding: 5px 10px; margin: 0 2px; text-decoration: none; border-radius: 6px; border: 1px solid #ccc;
}
.pagination a.active { background: #0aa05b; color: #fff; border: 1px solid #0aa05b; }
</style>
<script>
function sort(col) {
    let url = new URL(window.location.href);
    let curSort = url.searchParams.get("sort");
    let curOrder = url.searchParams.get("order");

    let order = "ASC";
    if (curSort === col && curOrder === "ASC") {
        order = "DESC";
    }
    url.searchParams.set("sort", col);
    url.searchParams.set("order", order);
    window.location.href = url.toString();
}

function exportCSV() {
    window.location.href = "survey_export_csv.php";
}

function exportPDF() {
    window.location.href = "survey_export_pdf.php";
}

function printView() {
    window.print();
}
</script>
</head>
<body>

<div class="container">
    <h2>Survey Responses</h2>

    <form method="GET" style="margin-bottom: 10px;">
        <input type="text" name="search" class="search" placeholder="Search name/email/account..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>

    <button class="export-btn" onclick="exportCSV()">Export CSV</button>
    <button class="export-btn" onclick="exportPDF()">Export PDF</button>
    <button class="print-btn" onclick="printView()">Print</button>

    <table cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th onclick="sort('id')">ID</th>
                <th onclick="sort('full_name')">Client Name</th>
                <th onclick="sort('account_number')">Account Number</th>
                <th onclick="sort('district')">District</th>
                <th onclick="sort('barangay')">Barangay</th>
                <th onclick="sort('location')">Location</th>
                <th onclick="sort('email')">Email</th>
                <th onclick="sort('feedback')">Feedback</th>
                <th onclick="sort('date_installed')">Date Installed</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$data): ?>
                <tr><td colspan="9" style="text-align:center;">No data found</td></tr>
            <?php else: ?>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['account_number']) ?></td>
                        <td><?= htmlspecialchars($row['district']) ?></td>
                        <td><?= htmlspecialchars($row['barangay']) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['feedback']) ?></td>
                        <td><?= htmlspecialchars($row['date_installed']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php for ($i=1; $i <= $pages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>"
                class="<?= ($i == $page) ? "active" : "" ?>">
            <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>

</div>

</body>
</html>
