<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: /csr");
    exit;
}

require __DIR__ . "/../../db_connect.php";

$csrUser = $_SESSION["csr_user"];
$search  = trim($_GET["search"] ?? "");

/* ============================================================
   Detect which column holds the assigned CSR in `users`
   (robust: tries several common names)
============================================================ */
$possibleUserCols = [
    'assigned_csr',
    'assigned_csr_user',
    'assigned_to',
    'assigned'
];

// Default to null (meaning: no column found)
$assignedUserCol = null;

$placeholders = "'" . implode("','", array_map('addslashes', $possibleUserCols)) . "'";
$sql = "
    SELECT column_name
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'users'
      AND column_name IN ($placeholders)
    LIMIT 1
";
try {
    $colStmt = $conn->query($sql);
    $foundCol = $colStmt->fetch(PDO::FETCH_COLUMN);
    if ($foundCol) {
        $assignedUserCol = $foundCol;
    }
} catch (Exception $e) {
    // If the information_schema query fails (very unlikely), leave $assignedUserCol null
    $assignedUserCol = null;
}

/* ============================================================
   BUILD CLIENTS QUERY — use detected column if available
============================================================ */
$queryBase = "
    SELECT 
        u.id, 
        u.account_number, 
        u.full_name, 
        u.email, 
        u.district, 
        u.barangay, 
        u.date_installed,
        (SELECT status FROM tickets WHERE client_id = u.id ORDER BY id DESC LIMIT 1) AS latest_status
    FROM users u
";

$params = [];

if ($assignedUserCol) {
    // safe: our $assignedUserCol is chosen from a whitelist, so embedding as identifier is OK
    $queryBase .= " WHERE u.{$assignedUserCol} = :csr ";
    $params[':csr'] = $csrUser;
} else {
    // No assignment column found — do not filter, but we add a small flag for the UI
    // NOTE: this will show all users if your schema truly lacks an assigned column.
    $queryBase .= " /* NO assigned CSR column found; showing all users */ ";
}

if ($search !== "") {
    // if we already have a WHERE, append AND, otherwise add WHERE
    $queryBase .= (strpos($queryBase, 'WHERE') !== false ? " AND " : " WHERE ");
    $queryBase .= " (u.full_name ILIKE :s OR u.account_number ILIKE :s OR u.email ILIKE :s) ";
    $params[':s'] = "%$search%";
}

$queryBase .= " ORDER BY u.full_name ASC";

$stmt = $conn->prepare($queryBase);
$stmt->execute($params);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total   = count($clients);

/* ============================================================
   COUNT TICKETS: UNRESOLVED / PENDING / RESOLVED
   (try assigned_csr in tickets; if not present, count overall)
============================================================ */
$ticketAssignedCol = null;
$possibleTicketCols = ['assigned_csr', 'assigned_to', 'assigned'];
$placeholders2 = "'" . implode("','", array_map('addslashes', $possibleTicketCols)) . "'";
$sql2 = "
    SELECT column_name
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'tickets'
      AND column_name IN ($placeholders2)
    LIMIT 1
";
try {
    $colStmt2 = $conn->query($sql2);
    $foundTicketCol = $colStmt2->fetch(PDO::FETCH_COLUMN);
    if ($foundTicketCol) $ticketAssignedCol = $foundTicketCol;
} catch (Exception $e) {
    $ticketAssignedCol = null;
}

$unresolved = $pending = $resolved = 0;

if ($ticketAssignedCol) {
    $countQuery = "
        SELECT status, COUNT(*) AS total
        FROM tickets
        WHERE {$ticketAssignedCol} = :csr
        GROUP BY status
    ";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute([":csr" => $csrUser]);
    $stats = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $unresolved = $stats['unresolved'] ?? 0;
    $pending    = $stats['pending'] ?? 0;
    $resolved   = $stats['resolved'] ?? 0;
} else {
    // fallback: count all tickets by status (no CSR scoping)
    $countStmt = $conn->query("SELECT status, COUNT(*) AS total FROM tickets GROUP BY status");
    $stats = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $unresolved = $stats['unresolved'] ?? 0;
    $pending    = $stats['pending'] ?? 0;
    $resolved   = $stats['resolved'] ?? 0;
}

/* ============================================================
   OUTPUT
============================================================ */
?>
<link rel="stylesheet" href="/CSR/clients/my_clients.css">

<h1>👥 My Clients</h1>

<!-- SUMMARY BAR -->
<div class="client-summary">
    <strong>Total Clients:</strong> <?= $total ?> &nbsp; | &nbsp;
    <strong>Unresolved:</strong> <?= $unresolved ?> &nbsp; | &nbsp;
    <strong>Pending:</strong> <?= $pending ?> &nbsp; | &nbsp;
    <strong>Resolved:</strong> <?= $resolved ?>
</div>

<?php if (!$assignedUserCol): ?>
    <div style="padding:10px;background:#fff3cd;border:1px solid #ffeeba;color:#856404;border-radius:6px;margin-bottom:12px;">
        <strong>Notice:</strong> No assignment column was detected in <code>users</code>.
        The list shows <em>all</em> users. If you expect filtering by CSR, add one of these columns to the <code>users</code> table:
        <code>assigned_csr</code>, <code>assigned_csr_user</code>, or <code>assigned_to</code>.
    </div>
<?php endif; ?>

<!-- EXPORT BUTTON -->
<div style="margin-bottom:15px;">
    <a href="/CSR/clients/print_daily_report.php" target="_blank" class="export-btn">📄 Export Daily Report (PDF)</a>
</div>

<!-- SEARCH BAR -->
<form method="GET" class="search-bar">
    <input type="hidden" name="tab" value="clients">
    <input type="text" name="search" placeholder="Search clients..." value="<?= htmlspecialchars($search) ?>">
    <button>Search</button>
</form>

<script>
function enc(route) {
    return "/home.php?v=" + btoa(route + "|" + Date.now());
}
function openChat(id) {
    window.location.href = enc("csr_chat") + "&client_id=" + id;
}
function openHistory(id) {
    window.location.href = enc("csr_clients") + "&client=" + id;
}
</script>

<!-- CLIENT TABLE -->
<div class="table-wrapper">
<table class="styled-table">
    <thead>
        <tr>
            <th>Account #</th>
            <th>Client Name</th>
            <th>Email</th>
            <th>District</th>
            <th>Barangay</th>
            <th>Date Installed</th>
            <th>Status</th>
            <th>Chat</th>
            <th>History</th>
        </tr>
    </thead>
    <tbody>

    <?php if ($total == 0): ?>
        <tr><td colspan="9" style="text-align:center;">No clients found</td></tr>
    <?php endif; ?>

    <?php foreach ($clients as $c):
        $status = strtolower($c["latest_status"] ?? "unresolved");
        switch ($status) {
            case "pending":  $badge = "<span class='badge pending'>PENDING</span>"; break;
            case "resolved": $badge = "<span class='badge resolved'>RESOLVED</span>"; break;
            default:         $badge = "<span class='badge unresolved'>UNRESOLVED</span>"; break;
        }
    ?>
        <tr>
            <td><?= htmlspecialchars($c['account_number']) ?></td>
            <td><?= htmlspecialchars($c['full_name']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td><?= htmlspecialchars($c['district']) ?></td>
            <td><?= htmlspecialchars($c['barangay']) ?></td>
            <td><?= htmlspecialchars($c['date_installed']) ?></td>
            <td><?= $badge ?></td>
            <td><button class="chat-btn" onclick="openChat(<?= $c['id'] ?>)">💬 Chat</button></td>
            <td><button class="history-btn" onclick="openHistory(<?= $c['id'] ?>)">📜 History</button></td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>
</div>
