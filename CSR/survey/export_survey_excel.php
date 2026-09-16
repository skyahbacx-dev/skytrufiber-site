<?php
require __DIR__ . "/../../db_connect.php";


/* Filters same as survey_responses.php */
$search     = $_GET['search'] ?? '';
$district   = $_GET['district'] ?? '';
$date_from  = $_GET['date_from'] ?? '';
$date_to    = $_GET['date_to'] ?? '';

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

$stmt = $conn->prepare("
    SELECT client_name, account_number, email, district, location, feedback, created_at
    FROM survey_responses
    $where
    ORDER BY created_at DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Start Excel Output */
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=survey_responses.xls");

echo "Client\tAccount #\tEmail\tDistrict\tLocation\tFeedback\tDate\n";

foreach ($rows as $r) {
    echo "{$r['client_name']}\t{$r['account_number']}\t{$r['email']}\t{$r['district']}\t{$r['location']}\t{$r['feedback']}\t" . date("Y-m-d", strtotime($r['created_at'])) . "\n";
}
