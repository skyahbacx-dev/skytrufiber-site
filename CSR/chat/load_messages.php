<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$client_id = intval($_POST["client_id"] ?? 0);
if ($client_id <= 0) exit("<p>Invalid client.</p>");

/* ============================================================
   FETCH TICKET STATUS
============================================================ */
$stmt = $conn->prepare("
    SELECT ticket_status
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

$ticketStatus = strtolower($client["ticket_status"] ?? "unresolved");

/* ============================================================
   FETCH TIMESTAMPS - REAL COLUMN = `timestamp`
============================================================ */

function getTimestamp($conn, $client_id, $statusName) {
    $stmt = $conn->prepare("
        SELECT timestamp
        FROM ticket_logs
        WHERE client_id = ? AND new_status = ?
        ORDER BY timestamp ASC
        LIMIT 1
    ");
    $stmt->execute([$client_id, $statusName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row["timestamp"] : null;
}

$resolvedAt = getTimestamp($conn, $client_id, "resolved");
$pendingAt  = getTimestamp($conn, $client_id, "pending");

/* ============================================================
   FETCH ALL CHAT MESSAGES
============================================================ */
$stmt = $conn->prepare("
    SELECT id, sender_type, message, deleted, edited, created_at
    FROM chat
    WHERE client_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "<p style='text-align:center;color:#777;padding:10px;'>No messages yet.</p>";
    exit;
}

/* ============================================================
   SYSTEM DIVIDER HTML
============================================================ */
$resolvedDividerHTML = $resolvedAt ? "
    <div class='system-divider'>
        <span>Ticket marked <strong>RESOLVED</strong> on " .
        date('M j, Y g:i A', strtotime($resolvedAt)) .
        "</span>
    </div>
" : "";

$pendingDividerHTML = $pendingAt ? "
    <div class='system-divider'>
        <span>Ticket placed <strong>PENDING</strong> on " .
        date('M j, Y g:i A', strtotime($pendingAt)) .
        "</span>
    </div>
" : "";

// Print once only
$printedResolved = false;
$printedPending  = false;

/* ============================================================
   RENDER MESSAGES
============================================================ */

foreach ($rows as $msg) {

    $msgTime = strtotime($msg["created_at"]);
    $timeFormatted = date("M j g:i A", $msgTime);

    // Insert PENDING divider
    if ($pendingAt && !$printedPending && $msgTime > strtotime($pendingAt)) {
        echo $pendingDividerHTML;
        $printedPending = true;
    }

    // Insert RESOLVED divider
    if ($resolvedAt && !$printedResolved && $msgTime > strtotime($resolvedAt)) {
        echo $resolvedDividerHTML;
        $printedResolved = true;
    }

    $id       = $msg["id"];
    $sender   = ($msg["sender_type"] === "csr") ? "sent" : "received";

    echo "
    <div class='message $sender' data-msg-id='$id'>
        
        <div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
        </div>

        <div class='message-content'>

            <button class='more-btn' data-id='$id'>
                <i class='fa-solid fa-ellipsis-vertical'></i>
            </button>

            <div class='message-bubble'>
    ";

    if ($msg["deleted"]) {
        echo "<div class='deleted-text'>🗑️ <i>This message was deleted</i></div>";
    } else {
        echo "<div class='msg-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</div>";
    }

    echo "</div>"; // bubble

    if ($msg["edited"] && !$msg["deleted"]) {
        echo "<div class='edited-label'>(edited)</div>";
    }

    echo "
            <div class='message-time'>$timeFormatted</div>
        </div>
    </div>
    ";
}

?>
