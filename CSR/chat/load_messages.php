<?php
/* ============================================================
   SESSION FIX — prevents CSR logout when customers log in
============================================================ */
ini_set("session.name", "CSRSESSID");
if (!isset($_SESSION)) session_start();

/* ============================================================
   Prevent caching
============================================================ */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: 0");
header("Pragma: no-cache");

require __DIR__ . "/../../db_connect.php";

/* ============================================================
   Validate ticket ID
============================================================ */
$ticket_id = intval($_POST["ticket_id"] ?? 0);
if ($ticket_id <= 0) {
    echo "<p>Invalid ticket.</p>";
    exit;
}

/* ============================================================
   Ensure CSR session exists
============================================================ */
$csrUser = $_SESSION["csr_user"] ?? null;

if (!$csrUser) {
    echo "SESSION_EXPIRED";
    exit;
}

/* ============================================================
   FETCH TICKET + USER META
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        t.status AS ticket_status,
        t.client_id,
        u.assigned_csr,
        u.ticket_lock
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo "<p>Ticket not found.</p>";
    exit;
}

$ticketStatus = strtolower($ticket["ticket_status"]);

/* ============================================================
   IF RESOLVED → READ ONLY MESSAGE
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
   FETCH ALL CHAT MESSAGES
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        id, sender_type, message, deleted, edited, created_at
    FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at ASC, id ASC
");
$stmt->execute([$ticket_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "<p style='text-align:center;color:#999;padding:10px;'>No messages yet.</p>";
    exit;
}

/* ============================================================
   DATE HELPERS
============================================================ */
$today     = date("Y-m-d");
$yesterday = date("Y-m-d", strtotime("-1 day"));

/* ============================================================
   RENDER MESSAGES WITH TODAY / YESTERDAY SEPARATORS
============================================================ */
$lastDate = null;

foreach ($rows as $msg):

    $id        = $msg["id"];
    $sender    = $msg["sender_type"];
    $deleted   = (bool)$msg["deleted"];
    $edited    = (bool)$msg["edited"];
    $createdAt = strtotime($msg["created_at"]);

    $currentDate = date("Y-m-d", $createdAt);

    /* --------------------------------------------
       DATE LABEL LOGIC
    --------------------------------------------- */
    if ($currentDate === $today) {
        $displayDate = "Today";
    } elseif ($currentDate === $yesterday) {
        $displayDate = "Yesterday";
    } else {
        $displayDate = date("F j, Y", $createdAt);
    }

    /* --------------------------------------------
       INSERT DATE SEPARATOR (STICKY)
    --------------------------------------------- */
    if ($currentDate !== $lastDate):
?>
        <div class="date-separator">
            <span><?= htmlspecialchars($displayDate) ?></span>
        </div>
<?php
        $lastDate = $currentDate;
    endif;

    $bubble  = nl2br(htmlspecialchars($msg["message"]));
    $msgTime = date("g:i A", $createdAt);
    $side    = ($sender === "csr") ? "sent" : "received";
?>
<div class="message <?= $side ?>" data-msg-id="<?= $id ?>">

    <?php if ($side === "received"): ?>
        <div class="message-avatar">
            <img src="/upload/default-avatar.png" alt="Client">
        </div>
    <?php else: ?>
        <div class="message-avatar"></div>
    <?php endif; ?>

    <!-- MORE BUTTON (CSR ONLY, NOT DELETED) -->
    <?php if ($sender === "csr" && !$deleted): ?>
        <button class="message-more-btn" data-id="<?= $id ?>" title="More options">
            <i class="fa-solid fa-ellipsis-vertical"></i>
        </button>
    <?php endif; ?>

    <div class="message-bubble">
        <?php if ($deleted): ?>
            <div class="deleted-text">🗑️ This message was deleted</div>
        <?php else: ?>
            <?= $bubble ?>
        <?php endif; ?>
    </div>

    <?php if ($edited && !$deleted): ?>
        <div class="edited-label" style="font-size:11px;color:#777;margin-top:2px;">
            (edited)
        </div>
    <?php endif; ?>

    <div class="message-time" style="font-size:11px;color:#777;margin-top:4px;">
        <?= $msgTime ?>
    </div>

</div>

<?php endforeach; ?>
