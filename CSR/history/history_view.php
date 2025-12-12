<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["csr_user"])) {
    header("Location: ../csr_login.php");
    exit;
}

require __DIR__ . "/../../db_connect.php";

/* ============================================================
   READ TICKET
============================================================ */
$ticketID = intval($_GET["ticket"] ?? 0);
if ($ticketID <= 0) exit("<h2>Invalid ticket ID.</h2>");

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

/* ============================================================
   LOAD CHAT MESSAGES
============================================================ */
$msgStmt = $conn->prepare("
    SELECT id, sender_type, message, deleted, edited, created_at
    FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at ASC
");
$msgStmt->execute([$ticketID]);
$messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

/* Count messages */
$csrCount = 0;
$clientCount = 0;
$deletedCount = 0;

foreach ($messages as $m) {
    if ($m["deleted"]) $deletedCount++;
    else if ($m["sender_type"] === "csr") $csrCount++;
    else $clientCount++;
}

/* ============================================================
   TIMELINE LOGS
============================================================ */
$logStmt = $conn->prepare("
    SELECT action, csr_user, timestamp
    FROM ticket_logs
    WHERE ticket_id = ?
    ORDER BY timestamp ASC
");
$logStmt->execute([$ticketID]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

/* Filter messages */
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

<style>
/* STATUS BADGES */
.status-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 13px;
    color: #fff;
    margin-left: 8px;
}
.status-unresolved { background:#d9534f; }
.status-pending { background:#f0ad4e; }
.status-resolved { background:#5cb85c; }

/* Export PDF Button */
.export-btn {
    background:#0a5ed7;
    padding:7px 14px;
    color:white;
    border-radius:6px;
    text-decoration:none;
    margin-left:10px;
}
.export-btn:hover { background:#094eb4; }

/* Fix layout spacing */
.history-two-col {
    display:flex;
    gap:20px;
    margin-top:20px;
}
.timeline-col, .chat-col {
    flex:1;
    background:#fff;
    padding:15px;
    border-radius:10px;
    border:1px solid #e6e6e6;
}

.chat-history {
    max-height:550px;
    overflow-y:auto;
    padding-right:10px;
}
</style>

<h2>
    📄 Ticket #<?= $ticketID ?> 
    <span class="status-badge status-<?= $status ?>"><?= strtoupper($status) ?></span>

    <a href="print_ticket.php?ticket=<?= $ticketID ?>" target="_blank" class="export-btn">
        ⬇ Export PDF
    </a>
</h2>

<a href="/home.php?v=<?= urlencode(base64_encode('csr_clients|' . time())) ?>&client=<?= $clientID ?>" 
   class="back-btn">← Back to Ticket History</a>

<p><strong>Client:</strong> <?= $clientName ?> (<?= $acctNo ?>)</p>
<p><strong>Created:</strong> <?= $createdAt ?></p>

<!-- MESSAGE COUNTS -->
<p style="margin-top:10px;">
    <strong>Messages:</strong> 
    CSR: <span style="color:#0a58ca;"><?= $csrCount ?></span> &nbsp;
    Client: <span style="color:#28a745;"><?= $clientCount ?></span> &nbsp;
    Deleted: <span style="color:#dc3545;"><?= $deletedCount ?></span>
</p>

<hr>

<!-- TWO COLUMN LAYOUT -->
<div class="history-two-col">

    <!-- TIMELINE -->
    <div class="timeline-col">
        <h3 class="section-header">📌 Ticket Timeline</h3>

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

    <!-- CHAT COLUMN -->
    <div class="chat-col">

        <h3 class="section-header">💬 Chat Messages</h3>

        <div class="filters">
            <a href="?ticket=<?= $ticketID ?>&filter=all" class="filter-btn <?= $filter=='all'?'active':'' ?>">All</a>
            <a href="?ticket=<?= $ticketID ?>&filter=csr" class="filter-btn <?= $filter=='csr'?'active':'' ?>">CSR</a>
            <a href="?ticket=<?= $ticketID ?>&filter=client" class="filter-btn <?= $filter=='client'?'active':'' ?>">Client</a>
            <a href="?ticket=<?= $ticketID ?>&filter=deleted" class="filter-btn <?= $filter=='deleted'?'active':'' ?>">Deleted</a>
        </div>

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
                    <?php if ($m["edited"]): ?><span class="edited">(edited)</span><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$found): ?>
            <div class="empty">No messages match this filter.</div>
        <?php endif; ?>
        </div>

        <!-- Floating Jump Buttons -->
        <button id="jumpTop" class="jump-btn">⬆</button>
        <button id="jumpBottom" class="jump-btn">⬇</button>

    </div>
</div>
