<?php
include '../db_connect.php';

$search = "%".($_GET['search'] ?? "")."%";
$from   = $_GET['from'] ?? "";
$to     = $_GET['to'] ?? "";
$month  = $_GET['month'] ?? "";

$params = [":s" => $search];

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

// Generate Excel file
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=survey_export.xls");

echo "<table border='1'>";
echo "<tr>
        <th>Client Name</th>
        <th>Account Number</th>
        <th>District</th>
        <th>Location</th>
        <th>Email</th>
        <th>Feedback</th>
        <th>Date Installed</th>
      </tr>";

foreach ($rows as $r) {
    echo "<tr>
            <td>".htmlspecialchars($r['client_name'])."</td>
            <td>".htmlspecialchars($r['account_number'])."</td>
            <td>".htmlspecialchars($r['district'])."</td>
            <td>".htmlspecialchars($r['location'])."</td>
            <td>".htmlspecialchars($r['email'])."</td>
            <td>".htmlspecialchars($r['remarks'])."</td>
            <td>".htmlspecialchars($r['created_at'])."</td>
          </tr>";
}

echo "</table>";
exit;
