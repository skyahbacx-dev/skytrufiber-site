<?php
require_once("../../vendor/autoload.php");
include "../../db_connect.php";

/* WEEKLY? */
$weekly = isset($_GET['weekly']);

if ($weekly) {
    $start = date("Y-m-d", strtotime("monday this week"));
    $end   = date("Y-m-d");

    $where = "WHERE created_at::date BETWEEN '$start' AND '$end'";
} else {
    $where = "";
}

/* Totals */
$total = $conn->query("SELECT COUNT(*) FROM survey_responses $where")->fetchColumn();

/* Districts */
$districts = $conn->query("
    SELECT district, COUNT(*) AS total 
    FROM survey_responses
    $where
    GROUP BY district ORDER BY district
")->fetchAll(PDO::FETCH_ASSOC);

/* Sentiment */
$feedback = $conn->query("
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
")->fetchAll(PDO::FETCH_ASSOC);

/* PDF BUILD */
$mpdf = new \Mpdf\Mpdf();
$html = "<h1>Survey Analytics Report</h1>";

if ($weekly) {
    $html .= "<h3>Weekly Report (" . $start . " to " . $end . ")</h3>";
}

$html .= "<p><b>Total Responses:</b> $total</p>";

$html .= "<h3>District Breakdown</h3><ul>";
foreach ($districts as $d) {
    $html .= "<li>{$d['district']}: {$d['total']}</li>";
}
$html .= "</ul>";

$html .= "<h3>Feedback Sentiment</h3><ul>";
foreach ($feedback as $f) {
    $html .= "<li>{$f['label']}: {$f['total']}</li>";
}
$html .= "</ul>";

$mpdf->WriteHTML($html);
$mpdf->Output("survey_analytics.pdf","I");
