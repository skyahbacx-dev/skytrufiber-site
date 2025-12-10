<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["csr_user"])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$ticketID = intval($_GET["ticket"] ?? 0);
if ($ticketID <= 0) {
    exit("<h2>Invalid ticket ID.</h2>");
}

/* ============================================================
   FETCH TICKET INFO
============================================================ */
$t = $conn->prepare("
    SELECT 
        t.id,
        t.client_id,
        t.status,
        t.created_at,
        u.full_name,
        u.account_number
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
");
$t->execute([$ticketID]);
$row = $t->fetch(PDO::FETCH_ASSOC);

if (!$row) exit("<h2>Ticket not found.</h2>");

$clientID   = $row["client_id"];
$clientName = htmlspecialchars($row["full_name"]);
$acctNo     = htmlspecialchars($row["account_number"]);
$status     = strtolower($row["status"]);
$createdAt  = date("M j, Y g:i A", strtotime($row["created_at"]));

/* ============================================================
   FETCH CHAT MESSAGES FOR THIS TICKET
============================================================ */
$msgs = $conn->prepare("
    SELECT 
        id,
        sender_type,
        message,
        deleted,
        edited,
        created_at
    FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at ASC
");
$msgs->execute([$ticketID]);
$messages = $msgs->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   FETCH TICKET LOGS
============================================================ */
$logs = $conn->prepare("
    SELECT action, csr_user, timestamp
    FROM ticket_logs
    WHERE client_id = ?
    ORDER BY timestamp ASC
");
$logs->execute([$clientID]);
$logRows = $logs->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="history.css">

<h2>📄 Ticket #<?= $ticketID ?> — <?= strtoupper($status) ?></h2>

<a href="../dashboard/csr_dashboard.php?tab=clients&client=<?= $clientID ?>" class="back-btn">
    ← Back to Ticket History
</a>

<p><strong>Client:</strong> <?= $clientName ?> (<?= $acctNo ?>)</p>
<p><strong>Created:</strong> <?= $createdAt ?></p>

<hr>

<!-- =========================================
     TIMELINE
========================================= -->
<h3>📌 Ticket Timeline</h3>

<div class="timeline">
<?php if (!$logRows): ?>
    <div class="empty">No timeline logs found.</div>
<?php else: ?>
    <?php foreach ($logRows as $log): ?>
        <div class="log-entry">
            <div class="log-action"><?= strtoupper($log["action"]) ?></div>
            <div class="log-by">by <?= htmlspecialchars($log["csr_user"]) ?></div>
            <div class="log-time"><?= date("M j, Y g:i A", strtotime($log["timestamp"])) ?></div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<hr>

<!-- =========================================
     CHAT FILTERS
========================================= -->
<h3>💬 Chat Messages</h3>

<div class="filters">
    <a href="?ticket=<?= $ticketID ?>&filter=all" class="filter-btn">All</a>
    <a href="?ticket=<?= $ticketID ?>&filter=csr" class="filter-btn">CSR</a>
    <a href="?ticket=<?= $ticketID ?>&filter=client" class="filter-btn">Client</a>
    <a href="?ticket=<?= $ticketID ?>&filter=deleted" class="filter-btn">Deleted</a>
</div>

<?php
$filter = $_GET["filter"] ?? "all";

function matchFilter($m, $filter) {
    if ($filter === "all") return true;
    if ($filter === "csr" && $m["sender_type"] === "csr") return true;
    if ($filter === "client" && $m["sender_type"] !== "csr") return true;
    if ($filter === "deleted" && $m["deleted"]) return true;
    return false;
}
?>

<div class="chat-history">
<?php
$found = false;
foreach ($messages as $m):
    if (!matchFilter($m, $filter)) continue;
    $found = true;
?>
    <div class="chat-msg <?= $m['sender_type'] ?>">
        <div class="bubble">
            <?php if ($m["deleted"]): ?>
                <i>🗑️ Message deleted</i>
            <?php else: ?>
                <?= nl2br(htmlspecialchars($m["message"])) ?>
            <?php endif; ?>
        </div>

        <div class="meta">
            <?= date("M j, Y g:i A", strtotime($m["created_at"])) ?>
            <?php if ($m["edited"]): ?>
                <span class="edited">(edited)</span>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php if (!$found): ?>
    <div class="empty">No messages match this filter.</div>
<?php endif; ?>
</div>
