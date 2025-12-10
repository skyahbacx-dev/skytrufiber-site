<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["csr_user"])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$csrUser = $_SESSION["csr_user"];
$clientID = intval($_GET["client"] ?? 0);

if ($clientID <= 0) {
    exit("<h2>Invalid client</h2>");
}

/* ============================================================
   FETCH CLIENT INFO
============================================================ */
$stmt = $conn->prepare("
    SELECT full_name, account_number
    FROM users
    WHERE id = ?
");
$stmt->execute([$clientID]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    exit("<h2>Client not found.</h2>");
}

$clientName = htmlspecialchars($client["full_name"]);
$acctNo     = htmlspecialchars($client["account_number"]);

/* ============================================================
   FETCH ALL TICKETS
============================================================ */
$tickets = $conn->prepare("
    SELECT id, status, created_at
    FROM tickets
    WHERE client_id = ?
    ORDER BY created_at DESC
");
$tickets->execute([$clientID]);
$list = $tickets->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="../chat/history.css">

<div class="history-wrapper">

<h2 class="history-title">📜 Ticket History — <?= $clientName ?> (<?= $acctNo ?>)</h2>

<a href="../dashboard/csr_dashboard.php?tab=clients" class="back-btn">
    ← Back to My Clients
</a>

<div class="history-list">

<?php if (!$list): ?>
    <div class="empty">No previous tickets found.</div>

<?php else: ?>
    <?php foreach ($list as $t): 
        $statusClass = strtolower($t["status"]);
    ?>

    <a class="ticket-card" href="../dashboard/csr_dashboard.php?tab=clients&ticket=<?= $t['id'] ?>">
        
        <div class="ticket-header">
            <span class="ticket-id">Ticket #<?= $t["id"] ?></span>
            <span class="ticket-status <?= $statusClass ?>">
                <?= strtoupper($t["status"]) ?>
            </span>
        </div>

        <div class="ticket-date">
            Created <?= date("M j, Y g:i A", strtotime($t["created_at"])) ?>
        </div>
    </a>

    <?php endforeach; ?>
<?php endif; ?>

</div>
</div>
