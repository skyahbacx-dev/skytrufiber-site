<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["csr_user"])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$ticketID = intval($_GET["ticket"] ?? 0);
if ($ticketID <= 0) exit("<h2>Invalid ticket.</h2>");

/* Get ticket info */
$stmt = $conn->prepare("
    SELECT t.*, u.full_name, u.account_number
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
");
$stmt->execute([$ticketID]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) exit("<h2>Ticket not found.</h2>");

$clientID = $ticket["client_id"];
$sortChat = $_GET["sort"] ?? "asc";

/* Get chat messages */
$msgQuery = "
    SELECT *
    FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at " . ($sortChat == "desc" ? "DESC" : "ASC");

$msgs = $conn->prepare($msgQuery);
$msgs->execute([$ticketID]);
$messages = $msgs->fetchAll(PDO::FETCH_ASSOC);

/* Get timeline logs */
$logs = $conn->prepare("
    SELECT action, csr_user, timestamp
    FROM ticket_logs
    WHERE client_id = ?
    ORDER BY timestamp ASC
");
$logs->execute([$clientID]);
$logRows = $logs->fetchAll(PDO::FETCH_ASSOC);
?>



<h2>📄 Ticket #<?= $ticketID ?> — <?= strtoupper($ticket["status"]) ?></h2>

<a href="../dashboard/csr_dashboard.php?tab=clients&client=<?= $clientID ?>" class="back-btn">← Back to Ticket History</a>

<p><strong>Client:</strong> <?= $ticket["full_name"] ?> (<?= $ticket["account_number"] ?>)</p>
<p><strong>Created:</strong> <?= date("M j, Y g:i A", strtotime($ticket["created_at"])) ?></p>

<hr>

<h3>📌 Ticket Timeline</h3>

<div class="timeline">
<?php foreach ($logRows as $log): 
    $cls = strtolower($log["action"]);
?>
    <div class="log-entry <?= $cls ?>">
        <div class="log-action"><?= strtoupper($log["action"]) ?></div>
        <div class="log-by">by <?= $log["csr_user"] ?></div>
        <div class="log-time"><?= date("M j, Y g:i A", strtotime($log["timestamp"])) ?></div>
    </div>
<?php endforeach; ?>
</div>

<hr>

<h3>💬 Chat Messages</h3>

<!-- Sort & Filter Tabs -->
<div class="filters">
    <a href="?ticket=<?= $ticketID ?>&filter=all" class="filter-btn">All</a>
    <a href="?ticket=<?= $ticketID ?>&filter=csr" class="filter-btn">CSR</a>
    <a href="?ticket=<?= $ticketID ?>&filter=client" class="filter-btn">Client</a>
    <a href="?ticket=<?= $ticketID ?>&filter=deleted" class="filter-btn">Deleted</a>

    <div class="sort-chat">
        <a href="?ticket=<?= $ticketID ?>&sort=asc" class="<?= $sortChat=='asc'?'active':'' ?>">Oldest</a>
        <a href="?ticket=<?= $ticketID ?>&sort=desc" class="<?= $sortChat=='desc'?'active':'' ?>">Newest</a>
    </div>
</div>

<!-- JUMP BUTTONS -->
<button id="jumpTop" class="jump-btn">⬆ Top</button>
<button id="jumpBottom" class="jump-btn">⬇ Bottom</button>

<div class="chat-history">
<?php
$filter = $_GET["filter"] ?? "all";
foreach ($messages as $m) {
    if ($filter == "csr" && $m["sender_type"] != "csr") continue;
    if ($filter == "client" && $m["sender_type"] == "csr") continue;
    if ($filter == "deleted" && !$m["deleted"]) continue;
?>
    <div class="chat-msg <?= $m['sender_type'] ?>">
        <div class="bubble <?= $m["deleted"] ? "deleted-bubble" : "" ?>">
            <?= $m["deleted"] ? "<i>🗑️ Deleted message</i>" : nl2br(htmlspecialchars($m["message"])) ?>
        </div>
        <div class="meta">
            <?= date("M j, Y g:i A", strtotime($m["created_at"])) ?>
            <?= $m["edited"] ? "<span class='edited'>(edited)</span>" : "" ?>
        </div>
    </div>
<?php } ?>
</div>

<script src="../history/history.js"></script>

