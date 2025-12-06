<?php
include '../../db_connect.php';

$view = $_GET['view'] ?? 'responses';

/* If analytics tab clicked */
if ($view === 'analytics') {
    include "analytics.php";
    return;
}

/* ---------------- FILTERS ---------------- */

$search     = $_GET['search'] ?? '';
$district   = $_GET['district'] ?? '';
$date_from  = $_GET['date_from'] ?? '';
$date_to    = $_GET['date_to'] ?? '';

$sort   = $_GET['sort'] ?? 'created_at';
$dir    = (isset($_GET['dir']) && strtolower($_GET['dir'])==='asc') ? 'ASC' : 'DESC';

$allowed = ["client_name","account_number","email","district","location","feedback","created_at"];
if (!in_array($sort,$allowed)) $sort="created_at";

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

/* Pagination */
$limit = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page-1)*$limit;

/* Count */
$countStmt = $conn->prepare("SELECT COUNT(*) FROM survey_responses $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows/$limit);

/* Fetch */
$query = "
    SELECT id, client_name, account_number, email, district, location, feedback, created_at
    FROM survey_responses
    $where ORDER BY $sort $dir
";
$stmt = $conn->prepare($query . " LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* District List */
$dList = $conn->query("SELECT DISTINCT district FROM survey_responses ORDER BY district")->fetchAll(PDO::FETCH_COLUMN);
?>

<link rel="stylesheet" href="survey_responses.css">

<h1>📄 Survey Responses</h1>

<!-- SUB TABS -->
<div class="survey-tabs">
    <a href="?tab=survey&view=responses" class="<?= $view==='responses'?'active':'' ?>">📝 Responses</a>
    <a href="?tab=survey&view=analytics" class="<?= $view==='analytics'?'active':'' ?>">📊 Analytics</a>
</div>

<!-- FILTERS -->
<form method="GET" class="filter-bar">
    <input type="hidden" name="tab" value="survey">

    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">

    <select name="district">
        <option value="">All Districts</option>
        <?php foreach($dList as $d): ?>
            <option value="<?= htmlspecialchars($d) ?>" <?= $district==$d?'selected':'' ?>>
                <?= htmlspecialchars($d) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Date:</label>
    <input type="date" name="date_from" value="<?= $date_from ?>">
    <input type="date" name="date_to" value="<?= $date_to ?>">

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
        </tr>
    </thead>
    <tbody>
    <?php foreach($rows as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['client_name']) ?></td>
            <td><?= htmlspecialchars($r['account_number']) ?></td>
            <td><?= htmlspecialchars($r['email']) ?></td>
            <td><?= htmlspecialchars($r['district']) ?></td>
            <td><?= htmlspecialchars($r['location']) ?></td>
            <td><?= htmlspecialchars($r['feedback']) ?></td>
            <td><?= date("Y-m-d", strtotime($r['created_at'])) ?></td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
</div>

<!-- PAGINATION -->
<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
    <a class="<?= $i==$page?'active':'' ?>"
       href="?tab=survey&page=<?= $i ?>&search=<?= urlencode($search) ?>&district=<?= urlencode($district) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&sort=<?= $sort ?>&dir=<?= $dir ?>">
       <?= $i ?>
    </a>
<?php endfor ?>
</div>

<script>
function sortBy(col){
    const url = new URL(window.location.href);
    url.searchParams.set("sort", col);
    url.searchParams.set("dir", url.searchParams.get("dir")==="ASC"?"DESC":"ASC");
    window.location.href = url.toString();
}
</script>
