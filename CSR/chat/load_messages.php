<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

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
$client_id    = intval($ticket["client_id"]);

/* ============================================================
   FETCH LOG TIMES (PENDING / RESOLVED)
   NOTE: ticket_logs uses client_id (NOT ticket_id)
============================================================ */
function getLogTime($conn, $client_id, $actionName) {
    $stmt = $conn->prepare("
        SELECT timestamp
        FROM ticket_logs
        WHERE client_id = ? AND action = ?
        ORDER BY timestamp ASC
        LIMIT 1
    ");
    $stmt->execute([$client_id, $actionName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row["timestamp"] : null;
}

$resolvedAt = getLogTime($conn, $client_id, "resolved");
$pendingAt  = getLogTime($conn, $client_id, "pending");

/* ============================================================
   FETCH CHAT MESSAGES — FILTERED BY ticket_id ONLY
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
   DIVIDER HTML
============================================================ */
$resolvedDividerHTML = $resolvedAt ? "
    <div class='system-divider'>
        <span>Ticket marked <strong>RESOLVED</strong> on " .
        date("M j, Y g:i A", strtotime($resolvedAt)) . "</span>
    </div>
" : "";

$pendingDividerHTML = $pendingAt ? "
    <div class='system-divider'>
        <span>Ticket placed <strong>PENDING</strong> on " .
        date("M j, Y g:i A", strtotime($pendingAt)) . "</span>
    </div>
" : "";

$printedResolved = false;
$printedPending  = false;

/* ============================================================
   RENDER MESSAGES
============================================================ */
foreach ($rows as $msg) {

    $id      = $msg["id"];
    $sender  = $msg["sender_type"];
    $msgTime = strtotime($msg["created_at"]);
    $timeFmt = date("M j g:i A", $msgTime);

    /* ------- Insert PENDING divider ------- */
    if ($pendingAt && !$printedPending && $msgTime > strtotime($pendingAt)) {
        echo $pendingDividerHTML;
        $printedPending = true;
    }

    /* ------- Insert RESOLVED divider ------- */
    if ($resolvedAt && !$printedResolved && $msgTime > strtotime($resolvedAt)) {
        echo $resolvedDividerHTML;
        $printedResolved = true;
    }

    /* ------- Determine message side ------- */
    $side = ($sender === "csr") ? "sent" : "received";

    echo "<div class='message $side' data-msg-id='$id'>";

    /* ------- Avatar for client messages ------- */
    if ($side === "received") {
        echo "
            <div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
            </div>
        ";
    } else {
        echo "<div class='message-avatar'></div>";
    }

    echo "<div class='message-content'>";

    /* ------- Action menu only for CSR messages ------- */
    if ($sender === "csr" && !$msg["deleted"]) {
        echo "
            <button class='more-btn' data-id='$id'>
                <i class='fa-solid fa-ellipsis-vertical'></i>
            </button>
        ";
    }

    /* ------- Message Bubble ------- */
    echo "<div class='message-bubble'>";

    if ($msg["deleted"]) {
        echo "<div class='deleted-text'>🗑️ <i>This message was deleted</i></div>";
    } else {
        echo "<div class='msg-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</div>";
    }

    echo "</div>"; // bubble

    /* ------- Edited Label ------- */
    if ($msg["edited"] && !$msg["deleted"]) {
        echo "<div class='edited-label'>(edited)</div>";
    }

    /* ------- Timestamp ------- */
    echo "<div class='message-time'>$timeFmt</div>";

    echo "</div></div>"; // end wrappers
}

?>
