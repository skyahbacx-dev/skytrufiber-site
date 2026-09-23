<?php
require __DIR__ . "/../../db_connect.php";

/* Date range filter: Today / 7 days / 30 days / Custom / All time (default) */
$range     = $_GET['range'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to'] ?? '';

$dateWhere = "";
$dateParams = [];
switch ($range) {
    case 'today':
        $dateWhere = "WHERE created_at::date = CURRENT_DATE";
        break;
    case '7d':
        $dateWhere = "WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'";
        break;
    case '30d':
        $dateWhere = "WHERE created_at >= CURRENT_DATE - INTERVAL '30 days'";
        break;
    case 'custom':
        if ($date_from !== '' && $date_to !== '') {
            $dateWhere = "WHERE created_at::date BETWEEN :df AND :dt";
            $dateParams = [':df' => $date_from, ':dt' => $date_to];
        }
        break;
}

/* Total responses */
$stmt = $conn->prepare("SELECT COUNT(*) FROM survey_responses $dateWhere");
$stmt->execute($dateParams);
$total = $stmt->fetchColumn();

/* District breakdown */
$stmt = $conn->prepare("
    SELECT COALESCE(district,'Unknown') AS district, COUNT(*) AS total
    FROM survey_responses
    $dateWhere
    GROUP BY district
    ORDER BY district
");
$stmt->execute($dateParams);
$districts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Real rating distribution (replaces keyword-guessed sentiment now that
   survey_responses.rating exists - see database/migrations/002_add_survey_rating.sql).
   Historical rows without a rating are simply excluded from this chart;
   nothing is backfilled or guessed. */
$ratingWhere = $dateWhere ? "$dateWhere AND rating IS NOT NULL" : "WHERE rating IS NOT NULL";
$stmt = $conn->prepare("
    SELECT rating, COUNT(*) AS total
    FROM survey_responses
    $ratingWhere
    GROUP BY rating
    ORDER BY rating
");
$stmt->execute($dateParams);
$ratingRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ratingLabels = ['1 😡','2 😕','3 😐','4 🙂','5 😍'];
$ratingCounts = array_fill(1, 5, 0);
foreach ($ratingRows as $r) { $ratingCounts[(int)$r['rating']] = (int)$r['total']; }

$stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(rating) AS rated_total FROM survey_responses $dateWhere");
$stmt->execute($dateParams);
$avgRatingRow = $stmt->fetch(PDO::FETCH_ASSOC);
$avgRating   = $avgRatingRow['avg_rating'] !== null ? round((float)$avgRatingRow['avg_rating'], 1) : null;
$ratedTotal  = (int)($avgRatingRow['rated_total'] ?? 0);

$rangeLabels = ['all' => 'All time', 'today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'custom' => 'Custom range'];
?>

<link rel="stylesheet" href="/assets/css/skytru.css">

<div class="sky-page-header">
    <div>
        <h1>Survey Analytics</h1>
        <p><?= $rangeLabels[$range] ?? 'All time' ?> — <?= $total ?> total responses<?= $ratedTotal ? " ({$ratedTotal} with a rating)" : "" ?>.</p>
    </div>
    <div style="display:flex; gap:8px;">
        <a class="sky-btn sky-btn-secondary sky-btn-sm" href="analytics_report.php" target="_blank">📄 Download PDF</a>
        <a class="sky-btn sky-btn-secondary sky-btn-sm" href="analytics_report.php?weekly=1" target="_blank">📆 Weekly Report</a>
    </div>
</div>

<div class="sky-tabs">
    <button class="sky-tab" onclick="Sky.goTab('survey')">📝 Responses</button>
    <button class="sky-tab active" onclick="Sky.goTab('survey_analytics')">📊 Analytics</button>
</div>

<form method="GET" class="sky-card" style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; margin-bottom:20px;">
    <input type="hidden" name="tab" value="survey_analytics">
    <div class="sky-tabs" style="border-bottom:none; margin-bottom:0;">
        <?php foreach (['all' => 'All time', 'today' => 'Today', '7d' => '7 days', '30d' => '30 days', 'custom' => 'Custom'] as $val => $label): ?>
            <button type="submit" name="range" value="<?= $val ?>" class="sky-tab <?= $range === $val ? 'active' : '' ?>"><?= $label ?></button>
        <?php endforeach; ?>
    </div>
    <?php if ($range === 'custom'): ?>
        <div class="sky-field" style="margin:0;"><label for="an-from">From</label><input type="date" id="an-from" name="date_from" class="sky-input" value="<?= htmlspecialchars($date_from) ?>"></div>
        <div class="sky-field" style="margin:0;"><label for="an-to">To</label><input type="date" id="an-to" name="date_to" class="sky-input" value="<?= htmlspecialchars($date_to) ?>"></div>
        <button class="sky-btn sky-btn-primary sky-btn-sm">Apply</button>
    <?php endif; ?>
</form>

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
    <div class="sky-card" style="height:320px;"><canvas id="districtChart"></canvas></div>
    <div class="sky-card" style="height:320px;"><canvas id="feedbackChart"></canvas></div>
</div>
<style>
@media (max-width: 800px) {
    .analytics-row { grid-template-columns: 1fr !important; }
}
</style>

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
        maintainAspectRatio: false,
        plugins: { title: { display: true, text: "Surveys per District" } }
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