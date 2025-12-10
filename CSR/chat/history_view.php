<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$csrUser = $_SESSION["csr_user"];
$ticketID = intval($_GET["ticket"] ?? 0);

if ($ticketID <= 0) {
    echo "<p>Invalid ticket.</p>";
    exit;
}

/* ============================================================
   FETCH TICKET INFO
============================================================ */
$stmt = $conn->prepare("
    SELECT t.id, t.client_id, t.status, t.created_at,
           u.full_name
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticketID]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo "<p>Ticket not found.</p>";
    exit;
}

$clientID   = $ticket["client_id"];
$clientName = htmlspecialchars($ticket["full_name"]);
$status     = strtoupper($ticket["status"]);
$createdAt  = date("M j, Y g:i A", strtotime($ticket["created_at"]));

/* ============================================================
   FETCH LOG ENTRIES (PENDING / RESOLVED)
============================================================ */
$logs = $conn->prepare("
    SELECT action, timestamp
    FROM ticket_logs
    WHERE client_id = ?
    ORDER BY timestamp ASC
");
$logs->execute([$clientID]);
$ticketLogs = $logs->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   FETCH CHAT MESSAGES (READ-ONLY)
============================================================ */
$stmt = $conn->prepare("
    SELECT sender_type, message, deleted, edited, created_at
    FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$ticketID]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat History - Ticket #<?= $ticketID ?></title>
    <link rel="stylesheet" href="history_view.css"> <!-- You will style separately -->
</head>
<body>

<div class="history-container">

    <h2>📜 Chat History — Ticket #<?= $ticketID ?></h2>

    <p><strong>Client:</strong> <?= $clientName ?></p>
    <p><strong>Created:</strong> <?= $createdAt ?></p>
    <p><strong>Status:</strong> <span class="ticket-status <?= strtolower($ticket["status"]) ?>"><?= $status ?></span></p>
    <hr>

    <h3>Ticket Timeline</h3>
    <div class="ticket-timeline">
        <?php if (empty($ticketLogs)): ?>
            <p>No timeline logs available.</p>
        <?php else: ?>
            <?php foreach ($ticketLogs as $log): ?>
                <div class="timeline-entry">
                    <strong><?= strtoupper($log["action"]) ?></strong> — 
                    <?= date("M j, Y g:i A", strtotime($log["timestamp"])) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <hr>

    <h3>Messages</h3>

    <div class="chat-history-box">
        <?php if (!$messages): ?>
            <p>No chat messages for this ticket.</p>
        <?php else: ?>
            <?php foreach ($messages as $m): ?>
                <?php
                    $sender = $m["sender_type"] === "csr" ? "CSR" : "Client";
                    $side   = $m["sender_type"] === "csr" ? "sent" : "received";
                    $time   = date("M j, Y g:i A", strtotime($m["created_at"]));
                ?>

                <div class="history-message <?= $side ?>">
                    <div class="history-bubble">
                        <?php if ($m["deleted"]): ?>
                            <i>🗑️ Message deleted</i>
                        <?php else: ?>
                            <?= nl2br(htmlspecialchars($m["message"])) ?>
                        <?php endif; ?>
                    </div>

                    <div class="history-meta">
                        <span><?= $sender ?></span> • <span><?= $time ?></span>
                        <?php if ($m["edited"] && !$m["deleted"]): ?>
                            <span class="edited-tag">(edited)</span>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <br>

    <a href="../dashboard/csr_dashboard.php?tab=clients" class="back-btn">⬅ Back to My Clients</a>

</div>

</body>
</html>
