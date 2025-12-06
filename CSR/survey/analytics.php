<?php
include '../../db_connect.php';

/* Totals */
$total = $conn->query("SELECT COUNT(*) FROM survey_responses")->fetchColumn();

/* District Breakdown */
$districts = $conn->query("
    SELECT district, COUNT(*) AS total 
    FROM survey_responses
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
    GROUP BY label
")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="survey_responses.css">

<h1>📊 Survey Analytics</h1>

<!-- Buttons -->
<div class="analytics-actions">
    <a class="export-btn" href="analytics_report.php" target="_blank">📄 Download Analytics PDF</a>
    <a class="export-btn" href="analytics_report.php?weekly=1" target="_blank">📆 Weekly Report</a>
</div>

<div class="analytics-block">
    <div class="metric-card">
        <h3>Total Surveys</h3>
        <p><?= $total ?></p>
    </div>

    <canvas id="districtChart"></canvas>
    <canvas id="feedbackChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// District
new Chart(document.getElementById("districtChart"), {
    type: "bar",
    data: {
        labels: <?= json_encode(array_column($districts, 'district')) ?>,
        datasets: [{ 
            label: "Surveys per District",
            data: <?= json_encode(array_column($districts, 'total')) ?>,
            backgroundColor: "#05702e"
        }]
    }
});

// Feedback
new Chart(document.getElementById("feedbackChart"), {
    type: "pie",
    data: {
        labels: <?= json_encode(array_column($feedback, 'label')) ?>,
        datasets: [{ 
            data: <?= json_encode(array_column($feedback, 'total')) ?>,
            backgroundColor: ["#0a7e3c","#f44336","#ff9800"]
        }]
    }
});
</script>

<style>
.analytics-actions {
    margin-bottom: 12px;
}
.export-btn {
    background: #05702e;
    color: white;
    padding: 8px 14px;
    border-radius: 8px;
    text-decoration:none;
    margin-right: 10px;
}
</style>
