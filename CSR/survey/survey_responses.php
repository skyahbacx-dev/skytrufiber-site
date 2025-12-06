<?php
include '../../db_connect.php';

$view = $_GET['view'] ?? 'responses';

/* If analytics tab clicked */
if ($view === 'analytics') {
    include "../dashboard/analytics.php";
    return;
}

/* --- Otherwise, show normal survey responses table --- */

$search = $_GET['search'] ?? '';
$sort   = $_GET['sort'] ?? 'created_at';
$dir    = (isset($_GET['dir']) && strtolower($_GET['dir'])==='asc') ? 'ASC':'DESC';

$allowed = ["client_name","account_number","email","district","location","feedback","created_at"];
if (!in_array($sort,$allowed)) $sort="created_at";

$where = "";
$params = [];
if ($search !== "") {
    $where = "WHERE client_name ILIKE :s 
            OR account_number ILIKE :s
            OR email ILIKE :s
            OR district ILIKE :s
            OR location ILIKE :s";
    $params[':s'] = "%$search%";
}

$limit = 10;
$page  = max(1, intval($_GET['page'] ?? 1));
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
?>

<link rel="stylesheet" href="survey_responses.css">

<h1>📄 Survey Responses</h1>

<!-- Sub-navigation inside Survey -->
<div class="survey-tabs">
    <a href="?tab=survey&view=responses" class="<?= $view==='responses'?'active':'' ?>">📝 Responses</a>
    <a href="?tab=survey&view=analytics" class="<?= $view==='analytics'?'active':'' ?>">📊 Analytics</a>
</div>

<form method="GET" class="search-section">
    <input type="hidden" name="tab" value="survey">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
    <button>Search</button>
</form>

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

<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
    <a class="<?= $i==$page?'active':'' ?>"
       href="?tab=survey&page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&dir=<?= $dir ?>">
       <?= $i ?>
    </a>
<?php endfor ?>
</div>

<script>
function sortBy(col){
    const url = new URL(window.location.href);
    url.searchParams.set("sort",col);
    const direction = url.searchParams.get("dir")==="ASC"?"DESC":"ASC";
    url.searchParams.set("dir",direction);
    url.searchParams.set("tab","survey");
    window.location.href=url.toString();
}
</script>
