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

if ($search !== "") {
    $where .= " AND (client_name ILIKE :s OR account_number ILIKE :s OR email ILIKE :s OR district ILIKE :s OR location ILIKE :s)";
    $params[':s'] = "%$search%";
}
if ($district !== "") {
    $where .= " AND district = :d";
    $params[':d'] = $district;
}
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
?>

<!DOCTYPE html>
<html>
<head>
<title>Print Survey Responses</title>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
}

h1 {
    text-align: center;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    border: 1px solid #ccc;
    padding: 8px;
    font-size: 14px;
}

th {
    background: #05702e;
    color: white;
}

.print-info {
    margin-bottom: 10px;
    font-size: 14px;
}
@media print {
    .no-print { display: none; }
}
</style>

</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding:10px 16px; background:#05702e; color:white; border:none; border-radius:6px; cursor:pointer;">
        🖨 Print Now
    </button>
</div>

<h1>Survey Responses Report</h1>

<div class="print-info">
    <strong>Filters Applied:</strong><br>
    Search: <?= htmlspecialchars($search ?: 'None') ?><br>
    District: <?= htmlspecialchars($district ?: 'All') ?><br>
    Date From: <?= htmlspecialchars($date_from ?: 'N/A') ?><br>
    Date To: <?= htmlspecialchars($date_to ?: 'N/A') ?><br>
</div>

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
            <td><?= htmlspecialchars($r['client_name']) ?></td>
            <td><?= htmlspecialchars($r['account_number']) ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= htmlspecialchars($r['district']) ?></td>
            <td><?= htmlspecialchars($r['location']) ?></td>
            <td><?= htmlspecialchars($r['feedback']) ?></td>
            <td><?= date("Y-m-d", strtotime($r['created_at'])) ?></td>
        </tr>
    <?php endforeach; ?>

</table>

</body>
</html>
