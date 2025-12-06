<?php
include '../../db_connect.php';

/* Count surveys */
$total = $conn->query("SELECT COUNT(*) FROM survey_responses")->fetchColumn();

/* District count */
$districts = $conn->query("
    SELECT district, COUNT(*) AS total 
    FROM survey_responses
    GROUP BY district ORDER BY district
")->fetchAll(PDO::FETCH_ASSOC);

/* Sentiment check */
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

<h2>📊 Survey Analytics</h2>

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
            data: <?= json_encode(array_column($districts, 'total')) ?>
        }]
    }
});

// Feedback
new Chart(document.getElementById("feedbackChart"), {
    type: "pie",
    data: {
        labels: <?= json_encode(array_column($feedback, 'label')) ?>,
        datasets: [{ data: <?= json_encode(array_column($feedback, 'total')) ?> }]
    }
});
</script>
