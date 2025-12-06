<?php
require_once("../../vendor/autoload.php");
include "../../db_connect.php";

/* -------------------------
   HANDLE WEEKLY MODE
-------------------------- */
$weekly = isset($_GET['weekly']);

if ($weekly) {
    $start = date("Y-m-d", strtotime("monday this week"));
    $end   = date("Y-m-d");

    $where = "WHERE created_at::date BETWEEN :start AND :end";
    $params = [":start" => $start, ":end" => $end];
    $title = "Weekly Survey Report ($start to $end)";
} else {
    $where = "";
    $params = [];
    $title = "Full Survey Analytics Report";
}

/* -------------------------
   TOTALS
-------------------------- */
$stmt = $conn->prepare("SELECT COUNT(*) FROM survey_responses $where");
$stmt->execute($params);
$total = $stmt->fetchColumn() ?? 0;

/* -------------------------
   DISTRICT COUNTS
-------------------------- */
$districtStmt = $conn->prepare("
    SELECT COALESCE(district, 'Unknown') AS district, COUNT(*) AS total
    FROM survey_responses
    $where
    GROUP BY district
    ORDER BY district
");
$districtStmt->execute($params);
$districts = $districtStmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------
   SENTIMENT ANALYSIS
-------------------------- */
$sentimentStmt = $conn->prepare("
    SELECT
        CASE 
            WHEN feedback ILIKE '%good%' OR feedback ILIKE '%fast%' THEN 'Positive'
            WHEN feedback ILIKE '%bad%' OR feedback ILIKE '%slow%' THEN 'Negative'
            ELSE 'Neutral'
        END AS label,
        COUNT(*) AS total
    FROM survey_responses
    $where
    GROUP BY label
    ORDER BY label
");
$sentimentStmt->execute($params);
$sentiments = $sentimentStmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------
   BUILD PDF HTML
-------------------------- */

$html = "
<style>
body { font-family: sans-serif; }
h1 { text-align: center; margin-bottom: 10px; }
h2 { margin-top: 25px; color:#05702e; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #ccc; padding: 8px; }
th { background: #05702e; color: white; }
.section { margin-top: 25px; }
.count-box {
    background: #e9f7ee;
    padding: 12px;
    border-radius: 8px;
    border-left: 5px solid #05702e;
    font-size: 16px;
    font-weight: bold;
}
</style>

<h1>$title</h1>

<div class='count-box'>
Total Survey Responses: <b>$total</b>
</div>

<h2>District Breakdown</h2>
<table>
    <tr><th>District</th><th>Total Responses</th></tr>";

foreach ($districts as $d) {
    $dist = htmlspecialchars($d['district'] ?? '');
    $count = htmlspecialchars($d['total'] ?? 0);
    $html .= "<tr><td>$dist</td><td>$count</td></tr>";
}

$html .= "</table>";

$html .= "
<h2>Feedback Sentiment</h2>
<table>
    <tr><th>Sentiment</th><th>Total Responses</th></tr>";

foreach ($sentiments as $s) {
    $label = htmlspecialchars($s['label'] ?? 'Neutral');
    $count = htmlspecialchars($s['total'] ?? 0);
    $html .= "<tr><td>$label</td><td>$count</td></tr>";
}

$html .= "</table>";

/* -------------------------
   OUTPUT PDF
-------------------------- */

$mpdf = new \Mpdf\Mpdf([
    'default_font_size' => 11,
    'default_font' => 'dejavusans'
]);

$mpdf->WriteHTML($html);
$mpdf->Output("survey_analytics_report.pdf","I");
exit;
