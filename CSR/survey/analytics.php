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

/* Real rating distribution (replaces keyword-guessed sentiment now that
   survey_responses.rating exists - see database/migrations/002_add_survey_rating.sql).
   Historical rows without a rating are simply excluded from this chart;
   nothing is backfilled or guessed. */
$ratingRows = $conn->query("
    SELECT rating, COUNT(*) AS total
    FROM survey_responses
    WHERE rating IS NOT NULL
    GROUP BY rating
    ORDER BY rating
")->fetchAll(PDO::FETCH_ASSOC);

$ratingLabels = ['1 😡','2 😕','3 😐','4 🙂','5 😍'];
$ratingCounts = array_fill(1, 5, 0);
foreach ($ratingRows as $r) { $ratingCounts[(int)$r['rating']] = (int)$r['total']; }

$avgRatingRow = $conn->query("SELECT AVG(rating) AS avg_rating, COUNT(rating) AS rated_total FROM survey_responses")
                     ->fetch(PDO::FETCH_ASSOC);
$avgRating   = $avgRatingRow['avg_rating'] !== null ? round((float)$avgRatingRow['avg_rating'], 1) : null;
$ratedTotal  = (int)($avgRatingRow['rated_total'] ?? 0);
?>

<link rel="stylesheet" href="/assets/css/skytru.css">

<div class="sky-page-header">
    <div>
        <h1>Survey Analytics</h1>
        <p>Based on <?= $total ?> total responses<?= $ratedTotal ? " ({$ratedTotal} with a rating)" : "" ?>.</p>
    </div>
    <div style="display:flex; gap:8px;">
        <a class="sky-btn sky-btn-secondary sky-btn-sm" href="analytics_report.php" target="_blank">📄 Download PDF</a>
        <a class="sky-btn sky-btn-secondary sky-btn-sm" href="analytics_report.php?weekly=1" target="_blank">📆 Weekly Report</a>
    </div>
</div>

<div class="sky-card-grid" style="margin-bottom: 24px;">
    <div class="sky-card sky-stat-card">
        <div class="sky-stat-label">Total Surveys</div>
        <div class="sky-stat-value"><?= htmlspecialchars($total) ?></div>
    </div>
    <div class="sky-card sky-stat-card">
        <div class="sky-stat-label">Average Rating</div>
        <div class="sky-stat-value"><?= $avgRating !== null ? $avgRating . ' / 5' : '—' ?></div>
        <div class="sky-stat-sub"><?= $ratedTotal ?> rated responses</div>
    </div>
</div>

<div class="analytics-row" style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="sky-card"><canvas id="districtChart"></canvas></div>
    <div class="sky-card"><canvas id="feedbackChart"></canvas></div>
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
            backgroundColor: "#0c8f3f"
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// RATING DISTRIBUTION CHART (real ratings, not guessed sentiment)
new Chart(document.getElementById("feedbackChart"), {
    type: "bar",
    data: {
        labels: <?= json_encode($ratingLabels) ?>,
        datasets: [{
            label: "Responses",
            data: <?= json_encode(array_values($ratingCounts)) ?>,
            backgroundColor: "#1a4fc4"
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { title: { display: true, text: "Rating Distribution" } }
    }
});
</script>
