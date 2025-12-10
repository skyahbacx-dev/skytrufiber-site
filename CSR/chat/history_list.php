<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$csrUser = $_SESSION['csr_user'];
$search  = trim($_GET["search"] ?? "");
$statusFilter = trim($_GET["status"] ?? "all");

/* ============================================================
   FETCH ALL TICKETS FOR THIS CSR’S CLIENTS
============================================================ */
$query = "
    SELECT 
        t.id AS ticket_id,
        t.client_id,
        t.status,
        t.created_at,
        t.resolved_at,
        u.full_name,
        u.account_number
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE u.assigned_csr = :csr
";

$params = [":csr" => $csrUser];

/* --- SEARCH CONDITIONS --- */
if ($search !== "") {
    $query .= " AND (u.full_name ILIKE :s OR u.account_number ILIKE :s OR t.id::TEXT ILIKE :s)";
    $params[":s"] = "%$search%";
}

/* --- STATUS FILTER --- */
if ($statusFilter !== "all") {
    $query .= " AND t.status = :st";
    $params[":st"] = $statusFilter;
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<link rel="stylesheet" href="history.css">

<div class="history-container">
    <h1>📘 Client Ticket History</h1>
    <div class="history-subtitle">View all past and resolved tickets for your assigned clients.</div>

    <!-- SEARCH + FILTER BAR -->
    <form method="GET" class="history-search-bar">
        <input type="text" name="search" placeholder="Search by name, ticket ID, account #..."
               value="<?= htmlspecialchars($search) ?>">

        <select name="status">
            <option value="all" <?= $statusFilter=="all"?"selected":"" ?>>All Status</option>
            <option value="unresolved" <?= $statusFilter=="unresolved"?"selected":"" ?>>Unresolved</option>
            <option value="pending" <?= $statusFilter=="pending"?"selected":"" ?>>Pending</option>
            <option value="resolved" <?= $statusFilter=="resolved"?"selected":"" ?>>Resolved</option>
        </select>

        <button type="submit">Filter</button>
    </form>

    <div class="table-wrapper">
        <table class="history-table" id="historyTable">
            <thead>
                <tr>
                    <th data-sort="number">Ticket ID</th>
                    <th data-sort="string">Client Name</th>
                    <th data-sort="string">Status</th>
                    <th data-sort="date">Created</th>
                    <th>View</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($tickets)) : ?>
                    <tr>
                        <td colspan="5" class="no-history">No tickets found.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td>#<?= $t["ticket_id"] ?></td>
                        <td><?= htmlspecialchars($t["full_name"]) ?></td>

                        <td>
                            <span class="status-badge status-<?= $t["status"] ?>">
                                <?= strtoupper($t["status"]) ?>
                            </span>
                        </td>

                        <td><?= date("M j, Y g:i A", strtotime($t["created_at"])) ?></td>

                        <td>
                            <a href="history_view.php?ticket=<?= $t["ticket_id"] ?>" class="view-btn">
                                📄 View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</div>

<script src="sort.js"></script>
