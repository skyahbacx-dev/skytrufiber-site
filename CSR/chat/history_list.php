<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$clientID = intval($_GET["client"] ?? 0);
if ($clientID <= 0) {
    exit("<p>Invalid client.</p>");
}

/* ============================================================
   FETCH CLIENT DETAILS
============================================================ */
$stmt = $conn->prepare("
    SELECT full_name, account_number
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$clientID]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) exit("Client not found");

$clientName = htmlspecialchars($client["full_name"]);
$clientAcc  = htmlspecialchars($client["account_number"]);

/* ============================================================
   FETCH ALL TICKETS FOR THIS CLIENT
============================================================ */
$stmt = $conn->prepare("
    SELECT id, status, created_at, resolved_at
    FROM tickets
    WHERE client_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$clientID]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat History - <?= $clientName ?></title>
    <link rel="stylesheet" href="history.css">
</head>

<body>

<h2>📜 Chat History — <?= $clientName ?> (<?= $clientAcc ?>)</h2>

<a href="../dashboard/csr_dashboard.php?tab=chat" class="back-btn">⬅ Back to Chat</a>

<div class="history-list">

<?php if (empty($tickets)): ?>
    <p>No ticket history found.</p>
<?php else: ?>

    <?php foreach ($tickets as $t): ?>
        <a class="ticket-box" href="history_view.php?ticket=<?= $t['id'] ?>">
            
            <div>
                <strong>Ticket #<?= $t["id"] ?></strong>
                <div>Status: 
                    <span class="status <?= strtolower($t['status']) ?>">
                        <?= strtoupper($t['status']) ?>
                    </span>
                </div>
            </div>

            <div class="ticket-dates">
                <small>Opened: <?= date("M d, Y g:i A", strtotime($t["created_at"])) ?></small>
                <?php if ($t["resolved_at"]): ?>
                    <br><small>Resolved: <?= date("M d, Y g:i A", strtotime($t["resolved_at"])) ?></small>
                <?php endif; ?>
            </div>

        </a>
    <?php endforeach; ?>

<?php endif; ?>

</div>

</body>
</html>
