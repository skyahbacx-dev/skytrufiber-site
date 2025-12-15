<?php
// ----------------------------
// UNIQUE SESSION FOR CSR ONLY
// ----------------------------
ini_set('session.name', 'CSRSESSID');
session_start();

require __DIR__ . "/../../db_connect.php";

// ----------------------------
// BLOCK if CSR not logged in
// ----------------------------
if (!isset($_SESSION['csr_user'])) {
    echo "Session expired.";
    exit;
}

$ticket_id = intval($_POST["ticket_id"] ?? 0);
if ($ticket_id <= 0) {
    echo "Invalid ticket.";
    exit;
}

// ------------------------------------------------------------
// Prevent caching
// ------------------------------------------------------------
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");


// ============================================================
// FETCH TICKET META
// ============================================================
$stmt = $conn->prepare("
    SELECT 
        t.status AS ticket_status,
        t.client_id,
        u.assigned_csr,
        u.ticket_lock
    FROM tickets t
    LEFT JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo "Ticket not found.";
    exit;
}

$csrUser = $_SESSION['csr_user'];
$ticketStatus = strtolower($ticket["ticket_status"]);
$isAssigned = ($ticket["assigned_csr"] === $csrUser);

// ============================================================
// RENDER MESSAGES (unchanged from your original)
// ============================================================

$stmt = $conn->prepare("
    SELECT id, sender_type, message, deleted, edited, created_at
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

foreach ($rows as $msg):
    $id = $msg["id"];
    $sender = $msg["sender_type"];
    $bubble = nl2br(htmlspecialchars($msg["message"]));
    $side = ($sender === "csr") ? "sent" : "received";
    $msgTime = date("M j • g:i A", strtotime($msg["created_at"]));
?>
<div class="message <?= $side ?>" data-msg-id="<?= $id ?>">
    <?php if ($side === "received"): ?>
        <div class="message-avatar"><img src="/upload/default-avatar.png"></div>
    <?php endif; ?>

    <div class="message-content">

        <?php if ($sender === "csr" && !$msg["deleted"]): ?>
            <button class="more-btn" data-id="<?= $id ?>">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
        <?php endif; ?>

        <div class="message-bubble">
            <?= $msg["deleted"] ? "<i>Message deleted</i>" : $bubble ?>
        </div>

        <div class="message-time"><?= $msgTime ?></div>
    </div>
</div>
<?php endforeach; ?>
