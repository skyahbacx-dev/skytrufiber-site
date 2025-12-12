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
   FETCH CLIENTS ASSIGNED TO CSR  (COLUMN FIXED)
============================================================ */
$query = "
    SELECT 
        u.id, 
        u.account_number, 
        u.full_name, 
        u.email, 
        u.district, 
        u.barangay, 
        u.date_installed,

        (SELECT status FROM tickets 
         WHERE client_id = u.id 
         ORDER BY id DESC LIMIT 1) AS latest_status

    FROM users u
    WHERE u.assigned_csr_user = :csr
";

$params = [":csr" => $csrUser];

if ($search !== "") {
    $query .= " 
        AND (
            u.full_name ILIKE :s OR 
            u.account_number ILIKE :s OR 
            u.email ILIKE :s
        )
    ";
    $params[":s"] = "%$search%";
}

$query .= " ORDER BY u.full_name ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total   = count($clients);

/* ============================================================
   COUNT TICKETS: UNRESOLVED / PENDING / RESOLVED
============================================================ */
$countQuery = "
    SELECT 
        status,
        COUNT(*) AS total
    FROM tickets
    WHERE assigned_csr = :csr
    GROUP BY status
";

$countStmt = $conn->prepare($countQuery);
$countStmt->execute([":csr" => $csrUser]);
$stats = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$unresolved = $stats["unresolved"] ?? 0;
$pending    = $stats["pending"] ?? 0;
$resolved   = $stats["resolved"] ?? 0;

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

<!-- EXPORT BUTTON -->
<div style="margin-bottom:15px;">
    <a href="/CSR/clients/print_daily_report.php" target="_blank" class="export-btn">
        📄 Export Daily Report (PDF)
    </a>
</div>

<!-- SEARCH BAR -->
<form method="GET" class="search-bar">
    <input type="hidden" name="tab" value="clients">
    <input type="text" name="search" placeholder="Search clients..."
           value="<?= htmlspecialchars($search) ?>">
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

            <td>
                <button class="chat-btn" onclick="openChat(<?= $c['id'] ?>)">💬 Chat</button>
            </td>

            <td>
                <button class="history-btn" onclick="openHistory(<?= $c['id'] ?>)">📜 History</button>
            </td>
        </tr>

    <?php endforeach; ?>

    </tbody>
</table>
</div>
