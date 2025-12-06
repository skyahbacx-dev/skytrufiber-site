<?php
if (!isset($csrUser)) {
    session_start();
    $csrUser = $_SESSION['csr_user'] ?? '';
}

include "../db_connect.php";

/* -------------------------
      FILTERS
-------------------------- */
$search = $_GET['search'] ?? '';
$sort   = $_GET['sort'] ?? 'full_name';
$dir    = (isset($_GET['dir']) && strtolower($_GET['dir']) === 'desc') ? 'DESC' : 'ASC';

$allowedSort = ["full_name", "account_number", "email", "district", "barangay", "date_installed"];
if (!in_array($sort, $allowedSort)) $sort = "full_name";

/* -------------------------
      BASE QUERY
-------------------------- */
$where = "WHERE csr_assigned = :csr";
$params = [":csr" => $csrUser];

/* Search Filter */
if ($search !== "") {
    $where .= " AND (full_name ILIKE :s OR account_number ILIKE :s OR email ILIKE :s)";
    $params[':s'] = "%$search%";
}

/* -------------------------
      PAGINATION
-------------------------- */
$limit = 12;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* Count total */
$countStmt = $conn->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

/* Fetch clients */
$stmt = $conn->prepare("
    SELECT id, full_name, account_number, email, district, barangay, date_installed
    FROM users
    $where
    ORDER BY $sort $dir
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="my_clients.css">

<div class="clients-container">

<h1>👥 My Assigned Clients</h1>

<p class="client-count">Total Assigned Clients: <b><?= $totalRows ?></b></p>

<!-- Search Bar -->
<form method="GET" class="client-search">
    <input type="hidden" name="tab" value="clients">
    <input type="text" name="search" placeholder="Search by name, account #, email..." 
           value="<?= htmlspecialchars($search) ?>">
    <button>Search</button>
</form>

<!-- Clients Table -->
<div class="table-wrapper">
<table class="styled-table">
    <thead>
        <tr>
            <th onclick="sortBy('full_name')">Name</th>
            <th onclick="sortBy('account_number')">Account #</th>
            <th onclick="sortBy('email')">Email</th>
            <th onclick="sortBy('district')">District</th>
            <th onclick="sortBy('barangay')">Barangay</th>
            <th onclick="sortBy('date_installed')">Installed</th>
        </tr>
    </thead>

    <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="empty">No clients assigned to you.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['full_name']) ?></td>
            <td><?= htmlspecialchars($c['account_number']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td><?= htmlspecialchars($c['district']) ?></td>
            <td><?= htmlspecialchars($c['barangay']) ?></td>
            <td><?= date("Y-m-d", strtotime($c['date_installed'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Pagination -->
<div class="pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a class="<?= $i == $page ? 'active' : '' ?>"
       href="?tab=clients&page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&dir=<?= $dir ?>">
       <?= $i ?>
    </a>
<?php endfor ?>
</div>

</div>

<script>
function sortBy(col){
    const url = new URL(window.location.href);
    url.searchParams.set("sort", col);
    url.searchParams.set("dir", url.searchParams.get("dir")==="ASC" ? "DESC" : "ASC");
    window.location.href = url.toString();
}
</script>
