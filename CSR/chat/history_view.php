<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$ticketID = intval($_GET["ticket"] ?? 0);
if ($ticketID <= 0) {
    die("<p>Invalid ticket.</p>");
}

/* ============================================================
   FETCH TICKET DETAILS
============================================================ */
$stmt = $conn->prepare("
    SELECT t.*, u.full_name, u.account_number
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
");
$stmt->execute([$ticketID]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) die("<p>Ticket not found.</p>");

$clientID = $ticket["client_id"];

/* ============================================================
   FETCH LOGS FOR TIMELINE
============================================================ */
$logs = $conn->prepare("
    SELECT action, timestamp, csr_user
    FROM ticket_logs
    WHERE client_id = ?
    ORDER BY timestamp ASC
");
$logs->execute([$clientID]);
$logData = $logs->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   FETCH CHAT MESSAGES FOR THIS TICKET
============================================================ */
$msgs = $conn->prepare("
    SELECT sender_type, message, created_at
    FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at ASC
");
$msgs->execute([$ticketID]);
$messages = $msgs->fetchAll(PDO::FETCH_ASSOC);

?>
<link rel="stylesheet" href="history.css">

<a href="history_list.php" class="back-btn">← Back</a>

<div class="history-container">

    <h1>📄 Ticket #<?= $ticketID ?> — <?= htmlspecialchars($ticket["full_name"]) ?></h1>
    <div class="history-subtitle">
        Account #: <?= htmlspecialchars($ticket["account_number"]) ?>
    </div>

    <!-- TIMELINE -->
    <h2>📌 Ticket Timeline</h2>
    <div class="timeline">

        <div class="timeline-item">
            <div class="timeline-title">Ticket Opened</div>
            <div class="timeline-date">
                <?= date("M j, Y g:i A", strtotime($ticket["created_at"])) ?>
            </div>
        </div>

        <?php foreach ($logData as $log): ?>
            <div class="timeline-item">
                <div class="timeline-title">
                    <?= ucfirst($log["action"]) ?> by <?= htmlspecialchars($log["csr_user"]) ?>
                </div>
                <div class="timeline-date">
                    <?= date("M j, Y g:i A", strtotime($log["timestamp"])) ?>
                </div>

                <span class="status-badge status-<?= $log["action"] ?>">
                    <?= strtoupper($log["action"]) ?>
                </span>
            </div>
        <?php endforeach; ?>

        <?php if ($ticket["resolved_at"]): ?>
            <div class="timeline-item">
                <div class="timeline-title">Ticket Resolved</div>
                <div class="timeline-date">
                    <?= date("M j, Y g:i A", strtotime($ticket["resolved_at"])) ?>
                </div>
                <span class="status-badge status-resolved">RESOLVED</span>
            </div>
        <?php endif; ?>

    </div>

    <!-- CHAT HISTORY -->
    <h2>💬 Chat History</h2>

    <div class="history-chat-box">

        <?php if (empty($messages)): ?>
            <div class="no-history">No chat messages for this ticket.</div>
        <?php endif; ?>

        <?php foreach ($messages as $m): ?>
            <div class="history-message <?= $m["sender_type"] === "csr" ? "sent" : "received" ?>">
                <div class="bubble">
                    <?= nl2br(htmlspecialchars($m["message"])) ?>
                    <div class="history-time">
                        <?= date("M j, Y g:i A", strtotime($m["created_at"])) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

</div>
