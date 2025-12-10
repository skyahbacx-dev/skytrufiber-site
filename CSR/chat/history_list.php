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

/* Fetch client */
$client = $conn->prepare("
    SELECT full_name, account_number
    FROM users
    WHERE id = ?
");
$client->execute([$clientID]);
$clientRow = $client->fetch(PDO::FETCH_ASSOC);

if (!$clientRow) {
    exit("<h2>Client not found.</h2>");
}

$clientName = htmlspecialchars($clientRow["full_name"]);
$acctNo     = htmlspecialchars($clientRow["account_number"]);

/* Fetch all tickets */
$tickets = $conn->prepare("
    SELECT id, status, created_at
    FROM tickets
    WHERE client_id = ?
    ORDER BY created_at DESC
");
$tickets->execute([$clientID]);
$list = $tickets->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="CSR/chat/history.css">

<h2>📜 Ticket History — <?= $clientName ?> (<?= $acctNo ?>)</h2>

<a href="../dashboard/csr_dashboard.php?tab=clients" class="back-btn">← Back to My Clients</a>

<div class="history-list">
<?php if (!$list): ?>
    <div class="empty">No previous tickets found.</div>
<?php else: ?>
    <?php foreach ($list as $t): ?>
        <a class="ticket-item" 
           href="../dashboard/csr_dashboard.php?tab=clients&ticket=<?= $t['id'] ?>">
           
            <div class="ticket-title">Ticket #<?= $t["id"] ?></div>

            <div class="ticket-status <?= strtolower($t["status"]) ?>">
                <?= strtoupper($t["status"]) ?>
            </div>

            <div class="ticket-date">
                Created <?= date("M j, Y g:i A", strtotime($t["created_at"])) ?>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>
</div>
