<?php
include "../../db_connect.php";

/* Filters (same as survey_responses.php) */
$search     = $_GET['search'] ?? '';
$district   = $_GET['district'] ?? '';
$date_from  = $_GET['date_from'] ?? '';
$date_to    = $_GET['date_to'] ?? '';
$sort       = $_GET['sort'] ?? 'created_at';
$dir        = (isset($_GET['dir']) && strtolower($_GET['dir']) === 'asc') ? 'ASC' : 'DESC';

$allowed = ["client_name","account_number","email","district","location","feedback","created_at"];
if (!in_array($sort, $allowed)) $sort = "created_at";

$where = "WHERE 1=1";
$params = [];

/* Search */
if ($search !== "") {
    $where .= " AND (
        client_name ILIKE :s OR
        account_number ILIKE :s OR
        email ILIKE :s OR
        district ILIKE :s OR
        location ILIKE :s
    )";
    $params[':s'] = "%$search%";
}

/* District filter */
if ($district !== "") {
    $where .= " AND district = :d";
    $params[':d'] = $district;
}

/* Date filters */
if ($date_from !== "") {
    $where .= " AND created_at::date >= :df";
    $params[':df'] = $date_from;
}
if ($date_to !== "") {
    $where .= " AND created_at::date <= :dt";
    $params[':dt'] = $date_to;
}

/* Fetch data */
$stmt = $conn->prepare("
    SELECT client_name, account_number, email, district, location, feedback, created_at
    FROM survey_responses
    $where
    ORDER BY $sort $dir
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$logoPath = "../../AHBALOGO.png";
?>

<!DOCTYPE html>
<html>
<head>
<title>Print Survey Responses</title>

<style>
@page { size: A4 landscape; margin: 20mm; }

body {
    font-family: Arial, sans-serif;
    padding: 20px;
    color: #000;
}

.header {
    text-align: center;
    margin-bottom: 20px;
}

.header img {
    height: 70px;
    margin-bottom: 10px;
}

.header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: bold;
}

.header h3 {
    margin: 4px 0 0;
    font-size: 16px;
    color: #444;
}

.print-info {
    margin-bottom: 10px;
    font-size: 14px;
    background: #f3f7f3;
    padding: 10px;
    border-left: 4px solid #05702e;
    border-radius: 6px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    border: 1px solid #ccc;
    padding: 7px;
    font-size: 12px;
}

th {
    background: #05702e;
    color: white;
    font-size: 13px;
}

.footer {
    position: fixed;
    bottom: 5px;
    left: 0;
    width: 100%;
    text-align: center;
    color: #444;
    font-size: 12px;
}

.no-print {
    margin-bottom: 20px;
}

.print-btn {
    padding: 10px 16px;
    background: #05702e;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

@media print {
    .no-print { display: none; }
    body { margin: 0; }
}
</style>

</head>
<body>

<!-- Auto-print when coming from export button -->
<?php if (isset($_GET['auto'])): ?>
<script>
window.onload = () => window.print();
</script>
<?php endif; ?>

<div class="no-print">
    <button onclick="window.print()" class="print-btn">🖨 Print Now</button>
</div>

<!-- HEADER WITH LOGO -->
<div class="header">
    <?php if (file_exists($logoPath)): ?>
        <img src="<?= $logoPath ?>" alt="Company Logo">
    <?php endif; ?>
    <h1>Survey Responses Report</h1>
    <h3>SkyTruFiber — Customer Insights Department</h3>
</div>

<!-- FILTER SUMMARY -->
<div class="print-info">
    <strong>Filters Applied:</strong><br>
    Search: <?= htmlspecialchars($search ?: 'None') ?><br>
    District: <?= htmlspecialchars($district ?: 'All') ?><br>
    Date From: <?= htmlspecialchars($date_from ?: 'N/A') ?><br>
    Date To: <?= htmlspecialchars($date_to ?: 'N/A') ?><br>
    Sorted By: <?= htmlspecialchars($sort) ?> (<?= $dir ?>)
</div>

<!-- TABLE -->
<table>
<tr>
    <th>Client</th>
    <th>Account #</th>
    <th>Email</th>
    <th>District</th>
    <th>Location</th>
    <th>Feedback</th>
    <th>Date</th>
</tr>

<?php foreach ($rows as $r): ?>
<tr>
    <td><?= htmlspecialchars($r['client_name'] ?? 'N/A') ?></td>
    <td><?= htmlspecialchars($r['account_number'] ?? 'N/A') ?></td>
    <td><?= htmlspecialchars($r['email'] ?? 'N/A') ?></td>
    <td><?= htmlspecialchars($r['district'] ?? 'N/A') ?></td>
    <td><?= htmlspecialchars($r['location'] ?? 'N/A') ?></td>
    <td><?= htmlspecialchars($r['feedback'] ?? 'N/A') ?></td>
    <td><?= !empty($r['created_at']) ? date("Y-m-d", strtotime($r['created_at'])) : 'N/A' ?></td>
</tr>
<?php endforeach; ?>

</table>

<!-- FOOTER -->
<div class="footer">
    Generated on <?= date("Y-m-d H:i:s") ?> — Page <span class="pageNumber"></span>
</div>

<script>
/* Auto insert page number on browsers that support it */
document.addEventListener("DOMContentLoaded", () => {
    const el = document.querySelector(".pageNumber");
    if (el) el.textContent = "";
});
</script>

</body>
</html>
