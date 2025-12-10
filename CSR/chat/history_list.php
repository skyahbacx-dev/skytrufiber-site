<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["csr_user"])) exit("Unauthorized");

require "../../db_connect.php";

$clientID = intval($_GET["client_id"] ?? 0);
if ($clientID <= 0) exit("Invalid client");

// =====================================================
// FETCH CLIENT NAME
// =====================================================
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$clientID]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) exit("Client not found.");
$clientName = htmlspecialchars($client["full_name"]);

// =====================================================
// FETCH ALL TICKETS FOR THIS CLIENT
// =====================================================
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

// =====================================================
// FETCH TICKET LOGS (actions: pending, resolved, etc.)
// =====================================================
$stmt = $conn->prepare("
    SELECT action, timestamp, csr_user
    FROM ticket_logs
    WHERE client_id = ?
    ORDER BY timestamp ASC
");
$stmt->execute([$clientID]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group logs by status
$logByAction = [];
foreach ($logs as $log) {
    $logByAction[$log["action"]] = $log["timestamp"];
}

?>
<link rel="stylesheet" href="history.css">

<h2 class="history-title">📜 Ticket History — <?= $clientName ?></h2>

<div class="history-list">

<?php if (empty($tickets)): ?>
    <p class="empty-history">No ticket history found.</p>
<?php endif; ?>

<?php foreach ($tickets as $t): ?>
    <div class="ticket-box">

        <div class="ticket-header">
            <span class="ticket-id">Ticket #<?= $t["id"] ?></span>
            <span class="ticket-status <?= strtolower($t["status"]) ?>">
                <?= strtoupper($t["status"]) ?>
            </span>
        </div>

        <div class="ticket-body">
            <p><strong>Created:</strong> <?= date("M j, Y g:i A", strtotime($t["created_at"])) ?></p>

            <?php if (!empty($logByAction["pending"])): ?>
                <p><strong>Pending:</strong> <?= date("M j, Y g:i A", strtotime($logByAction["pending"])) ?></p>
            <?php endif; ?>

            <?php if (!empty($logByAction["resolved"])): ?>
                <p><strong>Resolved:</strong> <?= date("M j, Y g:i A", strtotime($logByAction["resolved"])) ?></p>
            <?php endif; ?>

        </div>

        <a class="view-chat-btn"
           href="view_ticket_chat.php?ticket_id=<?= $t["id"] ?>&client_id=<?= $clientID ?>">
           💬 View Chat
        </a>

    </div>
<?php endforeach; ?>

</div>
