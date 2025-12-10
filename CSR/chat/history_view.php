<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION["csr_user"])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$ticketID = intval($_GET["ticket_id"] ?? 0);
$clientID = intval($_GET["client_id"] ?? 0);

if ($ticketID <= 0 || $clientID <= 0) {
    echo "<p>Invalid request.</p>";
    exit;
}

/* ============================================================
   FETCH CLIENT NAME
============================================================ */
$stmt = $conn->prepare("
    SELECT full_name 
    FROM users 
    WHERE id = ?
");
$stmt->execute([$clientID]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

/* ============================================================
   FETCH TICKET
============================================================ */
$stmt = $conn->prepare("
    SELECT id, status, created_at
    FROM tickets
    WHERE id = ?
");
$stmt->execute([$ticketID]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

/* ============================================================
   FETCH LOG EVENTS
============================================================ */
$stmt = $conn->prepare("
    SELECT action, timestamp
    FROM ticket_logs
    WHERE client_id = ?
    ORDER BY timestamp ASC
");
$stmt->execute([$clientID]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   FETCH CHAT MESSAGES
============================================================ */
$stmt = $conn->prepare("
    SELECT * FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$ticketID]);

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="history.css">

<div class="history-container">

    <h1>📄 Ticket #<?= $ticketID ?> — Chat History</h1>

    <div class="ticket-header">
        <strong>Client:</strong> <?= htmlspecialchars($client["full_name"]) ?><br>
        <strong>Status:</strong> <?= strtoupper($ticket["status"]) ?><br>
        <strong>Opened:</strong> <?= date("M j, Y g:i A", strtotime($ticket["created_at"])) ?>
    </div>

    <div class="history-chat-box">

        <?php
        $printedPending = false;
        $printedResolved = false;

        foreach ($messages as $m):
            $msgTime = strtotime($m["created_at"]);
            $side = ($m["sender_type"] === "csr") ? "sent" : "received";

            // DIVIDERS (based on ticket_logs)
            foreach ($logs as $log) {
                if ($log["action"] === "pending" && !$printedPending && $msgTime > strtotime($log["timestamp"])) {
                    echo "<div class='divider'>Ticket marked <strong>PENDING</strong> on " . date("M j, Y g:i A", strtotime($log["timestamp"])) . "</div>";
                    $printedPending = true;
                }

                if ($log["action"] === "resolved" && !$printedResolved && $msgTime > strtotime($log["timestamp"])) {
                    echo "<div class='divider'>Ticket <strong>RESOLVED</strong> on " . date("M j, Y g:i A", strtotime($log["timestamp"])) . "</div>";
                    $printedResolved = true;
                }
            }
        ?>

        <div class="msg <?= $side ?>">
            <div class="bubble">
                <?= nl2br(htmlspecialchars($m["message"])) ?>
            </div>
            <div class="time"><?= date("M j g:i A", strtotime($m["created_at"])) ?></div>
        </div>

        <?php endforeach; ?>

        <?php if (!$messages): ?>
            <p class="empty-row">No chat messages in this ticket.</p>
        <?php endif; ?>

    </div>

    <a href="history_list.php?client_id=<?= $clientID ?>" class="back-btn">⬅ Back</a>

</div>
