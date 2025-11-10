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

// Output headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=survey_export.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Write column headers
fputcsv($output, ["Client Name","Account Number","District","Location","Email","Feedback","Date Installed"]);

// Write data
foreach ($rows as $r) {
    fputcsv($output, [
        $r['client_name'],
        $r['account_number'],
        $r['district'],
        $r['location'],
        $r['email'],
        $r['remarks'],
        $r['created_at']
    ]);
}

fclose($output);
exit;
