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

/* Filter */
$filter = $_GET["filter"] ?? "all";

function matchFilter($m, $filter) {
    if ($filter === "all") return true;
    if ($filter === "csr" && $m["sender_type"] === "csr") return true;
    if ($filter === "client" && $m["sender_type"] !== "csr") return true;
    if ($filter === "deleted" && $m["deleted"]) return true;
    return false;
}
?>

<link rel="stylesheet" href="/history/history.css?v=<?= time(); ?>">
<script src="/history/history.js?v=<?= time(); ?>"></script>

<style>
.history-header {
    margin-bottom: 10px;
}

.export-buttons {
    margin: 10px 0 20px 0;
    display: flex;
    gap: 12px;
}

.export-btn {
    background: #1b5e20;
    color: white;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    transition: 0.2s;
}

.export-btn:hover {
    background: #0f3d12;
}

.export-btn.chat {
    background: #0a5db4;
}

.export-btn.chat:hover {
    background: #08498d;
}

.history-two-col {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 20px;
}

.timeline-col, .chat-col {
    background: white;
    padding: 18px;
    border-radius: 10px;
    border: 1px solid #dcdcdc;
}

.section-header {
    font-size: 18px;
    margin-bottom: 12px;
    font-weight: bold;
}

.timeline .log-entry {
    padding: 10px 12px;
    border-left: 4px solid #999;
    margin-bottom: 10px;
    background: #f9f9f9;
    border-radius: 6px;
}

.log-entry.unresolved { border-color: #d32f2f; }
.log-entry.resolved { border-color: #388e3c; }
.log-entry.pending { border-color: #fbc02d; }
.log-entry.assigned { border-color: #1565c0; }
.log-entry.unassigned { border-color: #6a1b9a; }

.log-action { font-weight: bold; }
.log-by { font-size: 13px; color: #555; }
.log-time { font-size: 12px; color: #777; margin-top: 2px; }

.chat-history {
    height: 570px;
    overflow-y: auto;
    padding-right: 10px;
}

.chat-msg {
    margin-bottom: 15px;
}

.chat-msg .bubble {
    padding: 10px 14px;
    border-radius: 8px;
    display: inline-block;
    max-width: 80%;
}

.chat-msg.csr .bubble {
    background: #e0f4d1;
}

.chat-msg.client .bubble {
    background: #d6e7ff;
}

.chat-msg.deleted .bubble {
    background: #f2f2f2;
    font-style: italic;
    color: #777;
}

.meta {
    font-size: 11px;
    color: #666;
    margin-top: 3px;
}

.filters {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}

.filter-btn {
    padding: 6px 12px;
    background: #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: black;
    font-size: 13px;
}

.filter-btn.active {
    background: #1b5e20;
    color: white;
}

.jump-btn {
    position: fixed;
    right: 30px;
    width: 43px;
    height: 43px;
    border-radius: 50%;
    border: none;
    background: #0b6e2b;
    color: white;
    cursor: pointer;
    font-size: 18px;
    bottom: 120px;
}

#jumpBottom {
    bottom: 60px;
}
</style>

<div class="history-header">
    <h2>📄 Ticket #<?= $ticketID ?> — <?= strtoupper($status) ?></h2>

    <a href="/home.php?v=<?= urlencode(base64_encode('csr_clients|' . time())) ?>&client=<?= $clientID ?>" 
       class="back-btn">
        ← Back to Ticket History
    </a>
</div>

<!-- EXPORT BUTTONS -->
<div class="export-buttons">
    <a class="export-btn" target="_blank" href="/CSR/history/print_ticket_report.php?ticket=<?= $ticketID ?>">
        📄 Export Report
    </a>

    <a class="export-btn chat" target="_blank" href="/CSR/history/print_ticket_chat.php?ticket=<?= $ticketID ?>">
        💬 Export Chat View
    </a>
</div>

<p><strong>Client:</strong> <?= $clientName ?> (<?= $acctNo ?>)</p>
<p><strong>Created:</strong> <?= $createdAt ?></p>

<hr>

<!-- MAIN 2-COLUMN LAYOUT -->
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

        <div class="filters">
            <a class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>" href="?ticket=<?= $ticketID ?>&filter=all">All</a>
            <a class="filter-btn <?= $filter === 'csr' ? 'active' : '' ?>" href="?ticket=<?= $ticketID ?>&filter=csr">CSR</a>
            <a class="filter-btn <?= $filter === 'client' ? 'active' : '' ?>" href="?ticket=<?= $ticketID ?>&filter=client">Client</a>
            <a class="filter-btn <?= $filter === 'deleted' ? 'active' : '' ?>" href="?ticket=<?= $ticketID ?>&filter=deleted">Deleted</a>
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

    </div>
</div>

<!-- FLOATING JUMP BUTTONS -->
<button id="jumpTop" class="jump-btn">⬆</button>
<button id="jumpBottom" class="jump-btn">⬇</button>
