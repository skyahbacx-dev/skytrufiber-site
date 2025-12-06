<?php
require_once("../../vendor/autoload.php");
include "../../db_connect.php";

/* Rebuild the same filters used in survey_responses.php */
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

/* Fetch data */
$stmt = $conn->prepare("
    SELECT client_name, account_number, email, district, location, feedback, created_at
    FROM survey_responses
    $where
    ORDER BY created_at DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Build PDF HTML */
$html = "
<style>
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ccc; padding: 8px; font-size: 12px; }
th { background: #05702e; color: white; }
h1 { text-align: center; }
</style>

<h1>Survey Responses Report</h1>

<table>
<tr>
    <th>Client</th>
    <th>Account #</th>
    <th>Email</th>
    <th>District</th>
    <th>Location</th>
    <th>Feedback</th>
    <th>Date</th>
</tr>";

foreach ($rows as $r) {
    $html .= "
    <tr>
        <td>{$r['client_name']}</td>
        <td>{$r['account_number']}</td>
        <td>{$r['email']}</td>
        <td>{$r['district']}</td>
        <td>{$r['location']}</td>
        <td>{$r['feedback']}</td>
        <td>" . date("Y-m-d", strtotime($r['created_at'])) . "</td>
    </tr>";
}

$html .= "</table>";

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output("survey_responses.pdf", "I");
