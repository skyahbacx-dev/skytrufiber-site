<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["csr_user"])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$clientID = intval($_GET["client_id"] ?? 0);
if ($clientID <= 0) {
    echo "<p>Invalid client.</p>";
    exit;
}

/* ============================================================
   FETCH CLIENT
============================================================ */
$stmt = $conn->prepare("
    SELECT full_name, account_number
    FROM users
    WHERE id = ?
");
$stmt->execute([$clientID]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    echo "<p>Client not found.</p>";
    exit;
}

/* ============================================================
   FETCH ALL TICKETS FOR THIS CLIENT
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        id,
        status,
        created_at
    FROM tickets
    WHERE client_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$clientID]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="history.css">

<div class="history-container">

    <h1>📜 Chat History</h1>

    <div class="client-header">
        <strong>Client:</strong> <?= htmlspecialchars($client["full_name"]) ?><br>
        <strong>Account #:</strong> <?= htmlspecialchars($client["account_number"]) ?>
    </div>

    <table class="styled-table">
        <thead>
            <tr>
                <th>Ticket #</th>
                <th>Status</th>
                <th>Date Created</th>
                <th>View</th>
            </tr>
        </thead>

        <tbody>
        <?php if (!$tickets): ?>
            <tr><td colspan="4" class="empty-row">No ticket history found.</td></tr>

        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td>#<?= $t["id"] ?></td>

                    <td>
                        <span class="badge <?= strtolower($t["status"]) ?>">
                            <?= strtoupper($t["status"]) ?>
                        </span>
                    </td>

                    <td><?= date("M j, Y g:i A", strtotime($t["created_at"])) ?></td>

                    <td>
                        <a class="view-btn"
                           href="history_view.php?ticket_id=<?= $t["id"] ?>&client_id=<?= $clientID ?>">
                           View
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <a href="../dashboard/csr_dashboard.php?tab=clients" class="back-btn">⬅ Back</a>

</div>
