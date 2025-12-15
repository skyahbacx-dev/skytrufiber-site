<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: 0");
header("Pragma: no-cache");

if (!isset($_SESSION)) session_start();
require __DIR__ . "/../../db_connect.php";

$ticket_id = intval($_POST["ticket_id"] ?? 0);
if ($ticket_id <= 0) exit("<p>Invalid ticket.</p>");

/* ============================================================
   FETCH TICKET & USER META (MATCHES YOUR REAL DB)
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        t.status AS ticket_status,
        t.client_id,
        u.assigned_csr,     -- correct: users table
        u.ticket_lock       -- correct: users table
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) exit("<p>Ticket not found.</p>");

$ticketStatus = strtolower($ticket["ticket_status"]);
$ticketLocked = ($ticket["ticket_lock"] ? true : false);
$csrUser      = $_SESSION["csr_user"] ?? null;

/* ============================================================
   FIXED ASSIGNMENT CHECK
============================================================ */
$isAssigned = ($ticket["assigned_csr"] === $csrUser);

/* ============================================================
   RESOLVED → SHOW READ ONLY
============================================================ */
if ($ticketStatus === "resolved") {
    echo "
    <p style='text-align:center;color:#777;padding:20px;'>
        This ticket has been <strong>resolved</strong>.<br>
        Chat history is available in <b>My Clients → Chat History</b>.
    </p>";
    exit;
}

/* ============================================================
   FETCH CHAT MESSAGES
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        id, sender_type, message, deleted, edited, created_at
    FROM chat
    WHERE ticket_id = ?
    ORDER BY id ASC
");
$stmt->execute([$ticket_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "<p style='text-align:center;color:#999;padding:10px;'>No messages yet.</p>";
    exit;
}

/* ============================================================
   RENDER MESSAGE BUBBLES
============================================================ */
foreach ($rows as $msg):

    $id      = $msg["id"];
    $sender  = $msg["sender_type"];
    $deleted = $msg["deleted"];
    $edited  = $msg["edited"];
    $bubble  = nl2br(htmlspecialchars($msg["message"]));
    $msgTime = date("M j • g:i A", strtotime($msg["created_at"]));
    $side    = ($sender === "csr") ? "sent" : "received";
?>
<div class="message <?= $side ?>" data-msg-id="<?= $id ?>">

    <?php if ($side === "received"): ?>
        <div class="message-avatar"><img src="/upload/default-avatar.png"></div>
    <?php else: ?>
        <div class="message-avatar"></div>
    <?php endif; ?>

    <div class="message-content">

        <?php if ($sender === "csr" && !$deleted): ?>
            <button class="more-btn" data-id="<?= $id ?>">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
        <?php endif; ?>

        <div class="message-bubble">
            <?php if ($deleted): ?>
                <div class="deleted-text">🗑️ <i>This message was deleted</i></div>
            <?php else: ?>
                <div class="msg-text"><?= $bubble ?></div>
            <?php endif; ?>
        </div>

        <?php if ($edited && !$deleted): ?>
            <div class="edited-label">(edited)</div>
        <?php endif; ?>

        <div class="message-time"><?= $msgTime ?></div>

    </div>
</div>

<?php endforeach; ?>
