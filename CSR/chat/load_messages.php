<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: 0");
header("Pragma: no-cache");

if (!isset($_SESSION)) session_start();
require __DIR__ . "/../../db_connect.php";

/* ============================================================
   READ ticket_id
============================================================ */
$ticket_id = intval($_POST["ticket_id"] ?? 0);
if ($ticket_id <= 0) exit("<p>Invalid ticket.</p>");

/* ============================================================
   FETCH TICKET + CLIENT
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        t.status AS ticket_status,
        t.client_id
    FROM tickets t
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) exit("<p>Ticket not found.</p>");

$ticketStatus = strtolower($ticket["ticket_status"]);

/* ============================================================
   BLOCK LOADING IF TICKET RESOLVED
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
   LOAD MESSAGES
============================================================ */
$stmt = $conn->prepare("
    SELECT id, sender_type, message, deleted, edited, created_at
    FROM chat
    WHERE ticket_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$ticket_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "<p style='text-align:center;color:#999;padding:10px;'>No messages yet.</p>";
    exit;
}

/* ============================================================
   RENDER MESSAGES
============================================================ */
foreach ($rows as $msg) {

    $id      = $msg["id"];
    $sender  = $msg["sender_type"];
    $msgTime = date("M j g:i A", strtotime($msg["created_at"]));
    $side    = ($sender === "csr") ? "sent" : "received";

    echo "<div class='message $side' data-msg-id='$id'>";

    if ($side === "received") {
        echo "
        <div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
        </div>";
    } else {
        echo "<div class='message-avatar'></div>";
    }

    echo "<div class='message-content'>";

    if ($sender === "csr" && !$msg["deleted"]) {
        echo "<button class='more-btn' data-id='$id'>
                <i class='fa-solid fa-ellipsis-vertical'></i>
              </button>";
    }

    echo "<div class='message-bubble'>";

    if ($msg["deleted"]) {
        echo "<div class='deleted-text'>🗑️ <i>This message was deleted</i></div>";
    } else {
        echo "<div class='msg-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</div>";
    }

    echo "</div>";

    if ($msg["edited"] && !$msg["deleted"]) {
        echo "<div class='edited-label'>(edited)</div>";
    }

    echo "<div class='message-time'>$msgTime</div>";

    echo "</div></div>";
}
?>
