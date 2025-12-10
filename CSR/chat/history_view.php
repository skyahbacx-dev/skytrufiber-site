<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['csr_user'])) {
    header("Location: ../csr_login.php");
    exit;
}

require "../../db_connect.php";

$ticketID = intval($_GET["ticket"] ?? 0);
if ($ticketID <= 0) exit("Invalid ticket");

/* ============================================================
   FETCH TICKET + CLIENT
============================================================ */
$stmt = $conn->prepare("
    SELECT t.id, t.status, t.client_id, u.full_name
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticketID]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) exit("Ticket not found");

$clientName = htmlspecialchars($ticket["full_name"]);
$ticketStatus = strtolower($ticket["status"]);
$clientID = intval($ticket["client_id"]);

/* ============================================================
   FETCH CHAT MESSAGES (READ ONLY)
============================================================ */
$stmt = $conn->prepare("
    SELECT sender_type, message, created_at, deleted, edited
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
    <title>Ticket #<?= $ticketID ?> History</title>
    <link rel="stylesheet" href="history.css">
</head>

<body>

<h2>📄 Ticket #<?= $ticketID ?> — <?= $clientName ?></h2>

<a href="history_list.php?client=<?= $clientID ?>" class="back-btn">⬅ Back to History List</a>

<div class="history-chat-box">

<?php if (empty($messages)): ?>
    <p>No chat messages found.</p>
<?php else: ?>

    <?php foreach ($messages as $m): ?>

        <div class="msg-row <?= $m['sender_type'] === 'csr' ? 'csr' : 'client' ?>">

            <div class="msg-bubble">

                <?php if ($m["deleted"]): ?>
                    <div class="deleted-text">🗑️ Message removed</div>
                <?php else: ?>
                    <?= nl2br(htmlspecialchars($m["message"])) ?>
                <?php endif; ?>

                <div class="msg-time">
                    <?= date("M d, Y g:i A", strtotime($m["created_at"])) ?>
                    <?php if ($m["edited"]): ?>
                        <span class="edited">(edited)</span>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</div>

</body>
</html>
