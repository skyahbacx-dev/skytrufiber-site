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
           sr.district, sr.location, sr.feedback, sr.created_at,
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

<!-- Correct CSS path -->
<link rel="stylesheet" href="/CSR/survey/survey_responses.css">

<h1>📄 Survey Responses</h1>

<!-- EXPORT BUTTONS -->
<div class="export-buttons">
    <a class="export-btn" href="/CSR/survey/export_survey_pdf.php?<?= http_build_query($_GET) ?>" target="_blank">📄 Export PDF</a>
    <a class="export-btn" href="/CSR/survey/export_survey_excel.php?<?= http_build_query($_GET) ?>">📊 Export Excel</a>
    <a class="export-btn" href="/CSR/survey/print_survey.php?<?= http_build_query($_GET) ?>" target="_blank">🖨 Print View</a>
</div>

<!-- SUB NAV TABS -->
<div class="survey-tabs">

    <!-- Responses -->
    <a href="?tab=survey&view=responses" 
       class="<?= $view === 'responses' ? 'active' : '' ?>">
       📝 Responses
    </a>

    <!-- Analytics (FIXED LINK) -->
    <a href="?tab=survey&view=analytics" 
       class="<?= $view === 'analytics' ? 'active' : '' ?>">
       📊 Analytics
    </a>
</div>

<!-- FILTER BAR -->
<form method="GET" class="filter-bar">
    <input type="hidden" name="tab" value="survey">

    <input type="text" name="search"
           value="<?= htmlspecialchars($search) ?>"
           placeholder="Search name, account #, email…">

    <select name="district">
        <option value="">All Districts</option>
        <?php foreach ($dList as $d): ?>
            <option value="<?= htmlspecialchars($d) ?>" <?= $district == $d ? 'selected' : '' ?>>
                <?= htmlspecialchars($d) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Date:</label>
    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">

    <button>Apply</button>
</form>

<!-- TABLE -->
<div class="table-wrapper">
    <table class="styled-table">
        <thead>
            <tr>
                <th onclick="sortBy('client_name')">Client</th>
                <th onclick="sortBy('account_number')">Account #</th>
                <th onclick="sortBy('email')">Email</th>
                <th onclick="sortBy('district')">District</th>
                <th onclick="sortBy('location')">Location</th>
                <th onclick="sortBy('feedback')">Feedback</th>
                <th onclick="sortBy('created_at')">Date</th>
                <th>User Link</th>
            </tr>
        </thead>
        <tbody>

        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['client_name']) ?></td>
                <td><?= htmlspecialchars($r['account_number']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= htmlspecialchars($r['district']) ?></td>
                <td><?= htmlspecialchars($r['location']) ?></td>
                <td><?= htmlspecialchars($r['feedback']) ?></td>
                <td><?= date("Y-m-d", strtotime($r['created_at'])) ?></td>

                <td>
                    <?php if (!empty($r['linked_name'])): ?>
                        <span style="color:#05702e;font-weight:bold;">✔ Linked (<?= htmlspecialchars($r['linked_name']) ?>)</span>
                    <?php else: ?>
                        <span style="color:#c00;font-weight:bold;">✖ No User</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach ?>

        </tbody>
    </table>
</div>

<!-- PAGINATION -->
<div class="pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a class="<?= $i == $page ? 'active' : '' ?>"
       href="?tab=survey&page=<?= $i ?>
            &search=<?= urlencode($search) ?>
            &district=<?= urlencode($district) ?>
            &date_from=<?= urlencode($date_from) ?>
            &date_to=<?= urlencode($date_to) ?>
            &sort=<?= urlencode($sort) ?>
            &dir=<?= urlencode($dir) ?>">
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
