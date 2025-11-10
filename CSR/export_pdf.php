<?php
include '../db_connect.php';

// Collect filters
$search = "%".($_GET['search'] ?? "")."%";
$from   = $_GET['from'] ?? "";
$to     = $_GET['to'] ?? "";
$month  = $_GET['month'] ?? "";

$params = [":s"=>$search];

$query = "
    SELECT client_name, account_number, district, location, email, feedback AS remarks, created_at
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

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine logo location
$logoPath = "../SKYTRUFIBER.png"; // adjust if needed
?>
<!DOCTYPE html>
<html>
<head>
<title>Survey Responses PDF Export</title>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
}

/* Logo */
.logo-container {
    text-align: center;
    margin-bottom: 20px;
}
.logo-container img {
    width: 140px;
    opacity: 0.9;
}

/* Title */
h2 {
    text-align: center;
    background: #009900;
    color: white;
    padding: 12px;
    border-radius: 6px;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
th, td {
    border: 1px solid #444;
    padding: 6px 8px;
}
th {
    background: #009900;
    color: #fff;
}

/* Landscape print layout */
@media print {
    @page {
        size: A4 landscape;
        margin: 10mm;
    }
    #printBtn {
        display: none;
    }
    body {
        margin: 0;
    }
}
</style>
</head>
<body>

<!-- Logo -->
<div class="logo-container">
    <img src="<?= $logoPath ?>" alt="SkyTruFiber Logo">
</div>

<!-- Title -->
<h2>Survey Responses Report</h2>

<!-- Print Button -->
<button id="printBtn"
        onclick="window.print()"
        style="padding:10px 15px; background:#009900; color:white; border:none; border-radius:5px; font-weight:bold; cursor:pointer; margin-bottom:20px;">
    📄 Export to PDF
</button>

<!-- Table -->
<table>
<thead>
<tr>
    <th>Client Name</th>
    <th>Account Number</th>
    <th>District</th>
    <th>Location</th>
    <th>Email</th>
    <th>Feedback</th>
    <th>Date Installed</th>
</tr>
</thead>
<tbody>
<?php if (!$rows): ?>
<tr>
    <td colspan="7" style="text-align:center; padding:20px;">No records found.</td>
</tr>
<?php else: ?>
<?php foreach ($rows as $r): ?>
<tr>
    <td><?= htmlspecialchars($r['client_name']) ?></td>
    <td><?= htmlspecialchars($r['account_number']) ?></td>
    <td><?= htmlspecialchars($r['district']) ?></td>
    <td><?= htmlspecialchars($r['location']) ?></td>
    <td><?= htmlspecialchars($r['email']) ?></td>
    <td><?= htmlspecialchars($r['remarks']) ?></td>
    <td><?= htmlspecialchars($r['created_at']) ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>

</body>
</html>
