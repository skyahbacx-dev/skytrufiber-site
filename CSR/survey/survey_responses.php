<?php
require __DIR__ . "/../../db_connect.php";

/* Determine view */
$view = $_GET['view'] ?? 'responses';

/* --------------------------------------
   LOAD ANALYTICS PAGE (ENCRYPTED ROUTING FIX)
--------------------------------------- */
if ($view === 'analytics') {

    // Always include the correct local file
    include __DIR__ . "/analytics.php";
    return;
}

/* --------------------------------------
   FILTERS
--------------------------------------- */
$search     = $_GET['search'] ?? '';
$district   = $_GET['district'] ?? '';
$date_from  = $_GET['date_from'] ?? '';
$date_to    = $_GET['date_to'] ?? '';

$sort   = $_GET['sort'] ?? 'created_at';
$dir    = (isset($_GET['dir']) && strtolower($_GET['dir']) === 'asc') ? 'ASC' : 'DESC';

$allowedColumns = [
    "client_name","account_number","email","district","location",
    "feedback","created_at","user_id"
];

if (!in_array($sort, $allowedColumns)) {
    $sort = "created_at";
}

$where = "WHERE 1=1";
$params = [];

/* Search filter */
if ($search !== "") {
    $where .= " AND (
        client_name ILIKE :s
        OR account_number ILIKE :s
        OR email ILIKE :s
        OR district ILIKE :s
        OR location ILIKE :s
    )";
    $params[':s'] = "%$search%";
}

/* District filter */
if ($district !== "") {
    $where .= " AND district = :d";
    $params[':d'] = $district;
}

/* Date range filters */
if ($date_from !== "") {
    $where .= " AND created_at::date >= :df";
    $params[':df'] = $date_from;
}

if ($date_to !== "") {
    $where .= " AND created_at::date <= :dt";
    $params[':dt'] = $date_to;
}

/* --------------------------------------
   PAGINATION
--------------------------------------- */
$limit = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$countStmt = $conn->prepare("SELECT COUNT(*) FROM survey_responses $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

/* --------------------------------------
   FETCH RESPONSES + USER LINK
--------------------------------------- */
$query = "
    SELECT sr.id, sr.user_id, sr.client_name, sr.account_number, sr.email,
           sr.district, sr.location, sr.feedback, sr.rating, sr.created_at,
           u.full_name AS linked_name
    FROM survey_responses sr
    LEFT JOIN users u ON u.id = sr.user_id
    $where
    ORDER BY $sort $dir
";

$stmt = $conn->prepare($query . " LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------
   DISTRICT LIST
--------------------------------------- */
$dList = $conn->query("
    SELECT DISTINCT district
    FROM survey_responses
    WHERE district IS NOT NULL AND district <> ''
    ORDER BY district
")->fetchAll(PDO::FETCH_COLUMN);
?>

<link rel="stylesheet" href="/assets/css/skytru.css">

<div class="sky-page-header">
    <div>
        <h1>Surveys</h1>
        <p><?= (int)$totalRows ?> total responses</p>
    </div>
    <div style="display:flex; gap:8px;">
        <a class="sky-btn sky-btn-secondary sky-btn-sm" href="/CSR/survey/export_survey_pdf.php?<?= http_build_query($_GET) ?>" target="_blank">📄 PDF</a>
        <a class="sky-btn sky-btn-secondary sky-btn-sm" href="/CSR/survey/export_survey_excel.php?<?= http_build_query($_GET) ?>">📊 Excel</a>
        <a class="sky-btn sky-btn-secondary sky-btn-sm" href="/CSR/survey/print_survey.php?<?= http_build_query($_GET) ?>" target="_blank">🖨 Print</a>
    </div>
</div>

<div class="sky-tabs">
    <button class="sky-tab <?= $view === 'responses' ? 'active' : '' ?>" onclick="Sky.goTab('survey')">📝 Responses</button>
    <button class="sky-tab <?= $view === 'analytics' ? 'active' : '' ?>" onclick="Sky.goTab('survey_analytics')">📊 Analytics</button>
</div>

<!-- FILTER BAR -->
<form method="GET" class="sky-card" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:20px;">
    <input type="hidden" name="tab" value="survey">

    <div class="sky-field" style="margin:0; flex:2; min-width:200px;">
        <label for="sv-search">Search</label>
        <input type="text" id="sv-search" name="search" class="sky-input"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Name, account #, email…">
    </div>

    <div class="sky-field" style="margin:0; min-width:160px;">
        <label for="sv-district">District</label>
        <select id="sv-district" name="district" class="sky-select">
            <option value="">All Districts</option>
            <?php foreach ($dList as $d): ?>
                <option value="<?= htmlspecialchars($d) ?>" <?= $district == $d ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="sky-field" style="margin:0;">
        <label for="sv-from">From</label>
        <input type="date" id="sv-from" name="date_from" class="sky-input" value="<?= htmlspecialchars($date_from) ?>">
    </div>
    <div class="sky-field" style="margin:0;">
        <label for="sv-to">To</label>
        <input type="date" id="sv-to" name="date_to" class="sky-input" value="<?= htmlspecialchars($date_to) ?>">
    </div>

    <button class="sky-btn sky-btn-primary">Apply</button>
</form>

<!-- TABLE -->
<div class="sky-table-wrap">
    <table class="sky-table">
        <thead>
            <tr>
                <th onclick="sortBy('client_name')" style="cursor:pointer;">Client</th>
                <th onclick="sortBy('account_number')" style="cursor:pointer;">Account #</th>
                <th onclick="sortBy('district')" style="cursor:pointer;">District</th>
                <th>Rating</th>
                <th onclick="sortBy('feedback')" style="cursor:pointer;">Feedback</th>
                <th onclick="sortBy('created_at')" style="cursor:pointer;">Date</th>
                <th>User Link</th>
            </tr>
        </thead>
        <tbody>

        <?php $emojiFace = [1 => '😡', 2 => '😕', 3 => '😐', 4 => '🙂', 5 => '😍']; ?>
        <?php if (empty($rows)): ?>
            <tr><td colspan="7"><div class="sky-empty"><div class="sky-empty__icon">📋</div>No responses match these filters.</div></td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td data-label="Client"><?= htmlspecialchars($r['client_name']) ?><br><span style="color:var(--gray-500); font-size:12px;"><?= htmlspecialchars($r['email']) ?></span></td>
                <td data-label="Account #"><?= htmlspecialchars($r['account_number']) ?></td>
                <td data-label="District"><?= htmlspecialchars($r['district']) ?><?= $r['location'] ? ', ' . htmlspecialchars($r['location']) : '' ?></td>
                <td data-label="Rating"><?= $r['rating'] ? ($emojiFace[(int)$r['rating']] ?? '') . ' ' . (int)$r['rating'] . '/5' : '<span class="sky-badge sky-badge-neutral">—</span>' ?></td>
                <td data-label="Feedback"><?= htmlspecialchars($r['feedback']) ?></td>
                <td data-label="Date"><?= date("M j, Y", strtotime($r['created_at'])) ?></td>

                <td data-label="User Link">
                    <?php if (!empty($r['linked_name'])): ?>
                        <span class="sky-badge sky-badge-success">✔ <?= htmlspecialchars($r['linked_name']) ?></span>
                    <?php else: ?>
                        <span class="sky-badge sky-badge-danger">✖ No User</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach ?>

        </tbody>
    </table>
</div>

<!-- PAGINATION -->
<div style="display:flex; gap:6px; margin-top:16px; flex-wrap:wrap;">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a class="sky-btn sky-btn-sm <?= $i == $page ? 'sky-btn-primary' : 'sky-btn-secondary' ?>"
       href="?tab=survey&page=<?= $i ?>&search=<?= urlencode($search) ?>&district=<?= urlencode($district) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&sort=<?= urlencode($sort) ?>&dir=<?= urlencode($dir) ?>">
       <?= $i ?>
    </a>
<?php endfor ?>
</div>

<script>
function sortBy(col){
    const url = new URL(window.location.href);
    url.searchParams.set("sort", col);
    url.searchParams.set("dir",
        url.searchParams.get("dir") === "ASC" ? "DESC" : "ASC"
    );
    window.location.href = url.toString();
}
</script>