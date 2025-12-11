<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["csr_user"])) {
    header("Location: ../csr_login.php");
    exit;
}

require __DIR__ . "/../../db_connect.php";


$ticketID = intval($_GET["ticket"] ?? 0);
if ($ticketID <= 0) exit("<h2>Invalid ticket ID.</h2>");

/* Fetch Ticket + Client info */
$stmt = $conn->prepare("
    SELECT t.id, t.client_id, t.status, t.created_at,
           u.full_name, u.account_number
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
");
$stmt->execute([$ticketID]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$t) exit("<h2>Ticket not found.</h2>");

$clientID   = $t["client_id"];
$clientName = htmlspecialchars($t["full_name"]);
$acctNo     = htmlspecialchars($t["account_number"]);
$status     = strtolower($t["status"]);
$createdAt  = date("M j, Y g:i A", strtotime($t["created_at"]));

/* Fetch chat messages */
$msgStmt = $conn->prepare("
    SELECT id, sender_type, message, deleted, edited, created_at
    FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at ASC
");
$msgStmt->execute([$ticketID]);
$messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

/* Fetch timeline logs */
$logStmt = $conn->prepare("
    SELECT action, csr_user, timestamp
    FROM ticket_logs
    WHERE client_id = ?
    ORDER BY timestamp ASC
");
$logStmt->execute([$clientID]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

/* Filter handler */
$filter = $_GET["filter"] ?? "all";

function matchFilter($m, $filter) {
    if ($filter === "all") return true;
    if ($filter === "csr" && $m["sender_type"] === "csr") return true;
    if ($filter === "client" && $m["sender_type"] !== "csr") return true;
    if ($filter === "deleted" && $m["deleted"]) return true;
    return false;
}
?>

<link rel="stylesheet" href="../history/history.css?v=<?= time(); ?>">
<script src="../history/history.js?v=<?= time(); ?>"></script>


<h2>📄 Ticket #<?= $ticketID ?> — <?= strtoupper($status) ?></h2>

href="/home.php?v=<?= urlencode(base64_encode('csr_clients|' . time())) ?>&ticket=<?= $t['id'] ?>"
 class="back-btn">
    ← Back to Ticket History
</a>

<p><strong>Client:</strong> <?= $clientName ?> (<?= $acctNo ?>)</p>
<p><strong>Created:</strong> <?= $createdAt ?></p>

<hr>

<!-- TWO COLUMN WRAPPER -->
<div class="history-two-col">

    <!-- LEFT COLUMN — TIMELINE -->
    <div class="timeline-col">
        <h3 class="section-header">📌 Ticket Timeline</h3>

        <div class="timeline">
        <?php if (!$logs): ?>
            <div class="empty">No timeline logs found.</div>
        <?php else: ?>

            <?php foreach ($logs as $log): ?>
                <?php
                    $action = strtolower($log["action"]);
                    $colorClass = match($action) {
                        "pending"     => "pending",
                        "unresolved"  => "unresolved",
                        "resolved"    => "resolved",
                        "assigned"    => "assigned",
                        "unassigned"  => "unassigned",
                        default       => "default"
                    };
                ?>
                <div class="log-entry <?= $colorClass ?>">
                    <div class="log-action"><?= strtoupper($log["action"]) ?></div>
                    <div class="log-by">by <?= htmlspecialchars($log["csr_user"]) ?></div>
                    <div class="log-time"><?= date("M j, Y g:i A", strtotime($log["timestamp"])) ?></div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT COLUMN — CHAT -->
    <div class="chat-col">

        <h3 class="section-header">💬 Chat Messages</h3>

        <!-- FILTER TABS -->
        <div class="filters">
            <a href="?ticket=<?= $ticketID ?>&filter=all"
               class="filter-btn <?= $filter==='all'?'active':'' ?>">All</a>

            <a href="?ticket=<?= $ticketID ?>&filter=csr"
               class="filter-btn <?= $filter==='csr'?'active':'' ?>">CSR</a>

            <a href="?ticket=<?= $ticketID ?>&filter=client"
               class="filter-btn <?= $filter==='client'?'active':'' ?>">Client</a>

            <a href="?ticket=<?= $ticketID ?>&filter=deleted"
               class="filter-btn <?= $filter==='deleted'?'active':'' ?>">Deleted</a>
        </div>

        <!-- CHAT CONTENT SCROLLER -->
        <div class="chat-history" id="chatHistory">

        <?php
        $found = false;

        foreach ($messages as $m):
            if (!matchFilter($m, $filter)) continue;
            $found = true;

            $type = $m["deleted"] ? "deleted" : strtolower($m["sender_type"]);
        ?>
            <div class="chat-msg <?= $type ?>">
                <div class="bubble">
                    <?php if ($m["deleted"]): ?>
                        🗑️ <i>Message deleted</i>
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

        <!-- FLOATING JUMP BUTTONS (handled by your history.js) -->
        <button id="jumpTop" class="jump-btn">⬆</button>
        <button id="jumpBottom" class="jump-btn">⬇</button>

    </div>
</div>
