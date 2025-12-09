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
   FETCH TIMESTAMPS FOR SYSTEM DIVIDERS
============================================================ */

function getTimestamp($conn, $client_id, $action) {
    $stmt = $conn->prepare("
        SELECT changed_at 
        FROM ticket_logs
        WHERE client_id = ? AND new_status = ?
        ORDER BY changed_at ASC 
        LIMIT 1
    ");
    $stmt->execute([$client_id, $action]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row["changed_at"] : null;
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
        <span>Ticket marked <strong>RESOLVED</strong> on " . date("M j, Y g:i A", strtotime($resolvedAt)) . "</span>
    </div>
" : "";

$pendingDividerHTML = $pendingAt ? "
    <div class='system-divider'>
        <span>Ticket placed <strong>PENDING</strong> on " . date("M j, Y g:i A", strtotime($pendingAt)) . "</span>
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

    // Insert Pending divider
    if ($pendingAt && !$printedPending && $msgTime > strtotime($pendingAt)) {
        echo $pendingDividerHTML;
        $printedPending = true;
    }

    // Insert Resolved divider
    if ($resolvedAt && !$printedResolved && $msgTime > strtotime($resolvedAt)) {
        echo $resolvedDividerHTML;
        $printedResolved = true;
    }

    // Determine message direction
    $senderClass = ($msg["sender_type"] === "csr") ? "sent" : "received";
    $msgID       = $msg["id"];

    echo "
    <div class='message $senderClass' data-msg-id='$msgID'>
        
        <!-- Avatar -->
        <div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
        </div>

        <div class='message-content'>
            
            <!-- Action Menu Button -->
            <button class='more-btn' data-id='$msgID'>
                <i class='fa-solid fa-ellipsis-vertical'></i>
            </button>

            <!-- Bubble -->
            <div class='message-bubble'>
    ";

    if ($msg["deleted"]) {
        echo "<div class='deleted-text'>🗑️ <i>This message was deleted</i></div>";
    } else {
        echo "<div class='msg-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</div>";
    }

    echo "</div>"; // END bubble

    // Edited badge
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
