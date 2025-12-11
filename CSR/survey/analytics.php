<?php
require __DIR__ . "/../../db_connect.php";


/* Total responses */
$total = $conn->query("SELECT COUNT(*) FROM survey_responses")->fetchColumn();

/* District breakdown */
$districts = $conn->query("
    SELECT COALESCE(district,'Unknown') AS district, COUNT(*) AS total
    FROM survey_responses
    GROUP BY district
    ORDER BY district
")->fetchAll(PDO::FETCH_ASSOC);

/* Sentiment grouping */
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
    ORDER BY label
")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../survey/survey_responses.css">

<div class="survey-analytics-container">

    <h1>📊 Survey Analytics</h1>

    <div class="analytics-actions">
        <a class="export-btn" href="analytics_report.php" target="_blank">📄 Download Analytics PDF</a>
        <a class="export-btn" href="analytics_report.php?weekly=1" target="_blank">📆 Weekly Report</a>
    </div>

    <!-- TOTAL SURVEYS CARD -->
    <h3>Total Surveys</h3>
    <div class="metric-card"><?= htmlspecialchars($total) ?></div>

    <!-- SIDE-BY-SIDE CHARTS -->
    <div class="analytics-row">

        <div class="analytics-box">
            <canvas id="districtChart"></canvas>
        </div>

        <div class="analytics-box">
            <canvas id="feedbackChart"></canvas>
        </div>

    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// DISTRICT CHART
new Chart(document.getElementById("districtChart"), {
    type: "bar",
    data: {
        labels: <?= json_encode(array_column($districts, 'district')) ?>,
        datasets: [{
            label: "Surveys per District",
            data: <?= json_encode(array_column($districts, 'total')) ?>,
            backgroundColor: "#05702e"
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// SENTIMENT PIE CHART
new Chart(document.getElementById("feedbackChart"), {
    type: "pie",
    data: {
        labels: <?= json_encode(array_column($feedback, 'label')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($feedback, 'total')) ?>,
            backgroundColor: ["#0a7e3c","#f44336","#ff9800"]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>
