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
   GET LOG TIMESTAMPS (resolved / pending)
============================================================ */

$resolvedAt = null;
$pendingAt  = null;

// RESOLVED TIMESTAMP
$log = $conn->prepare("
    SELECT timestamp
    FROM ticket_logs
    WHERE client_id = ?
      AND action = 'resolved'
    ORDER BY timestamp ASC LIMIT 1
");
$log->execute([$client_id]);
$resolvedRow = $log->fetch(PDO::FETCH_ASSOC);
if ($resolvedRow) $resolvedAt = $resolvedRow["timestamp"];

// PENDING TIMESTAMP
$log = $conn->prepare("
    SELECT timestamp
    FROM ticket_logs
    WHERE client_id = ?
      AND action = 'pending'
    ORDER BY timestamp ASC LIMIT 1
");
$log->execute([$client_id]);
$pendingRow = $log->fetch(PDO::FETCH_ASSOC);
if ($pendingRow) $pendingAt = $pendingRow["timestamp"];

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
   SYSTEM DIVIDERS (HTML TEMPLATES)
============================================================ */

$resolvedDivider = $resolvedAt ? "
    <div class='system-divider'>
        <span>Ticket marked <strong>RESOLVED</strong> on " .
        date("M j, Y g:i A", strtotime($resolvedAt)) .
        "</span>
    </div>
" : "";

$pendingDivider = $pendingAt ? "
    <div class='system-divider'>
        <span>Ticket placed <strong>PENDING</strong> / ON HOLD on " .
        date("M j, Y g:i A", strtotime($pendingAt)) .
        "</span>
    </div>
" : "";

// Flags so each divider appears only once
$printedResolvedDivider = false;
$printedPendingDivider  = false;

/* ============================================================
   RENDER MESSAGES
============================================================ */

foreach ($rows as $msg) {

    $msgTime = strtotime($msg["created_at"]);

    // Insert PENDING divider when needed
    if (
        $pendingAt &&
        !$printedPendingDivider &&
        $msgTime > strtotime($pendingAt)
    ) {
        echo $pendingDivider;
        $printedPendingDivider = true;
    }

    // Insert RESOLVED divider when needed
    if (
        $resolvedAt &&
        !$printedResolvedDivider &&
        $msgTime > strtotime($resolvedAt)
    ) {
        echo $resolvedDivider;
        $printedResolvedDivider = true;
    }

    /* ===============================
       Render individual message
    =============================== */

    $id       = $msg["id"];
    $sender   = ($msg["sender_type"] === "csr") ? "sent" : "received";
    $timeText = date("M j g:i A", $msgTime);

    echo "<div class='message $sender' data-msg-id='$id'>";

    // Avatar
    echo "
        <div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
        </div>
    ";

    echo "<div class='message-content'>";

    // More button
    echo "
        <button class='more-btn' data-id='$id'>
            <i class='fa-solid fa-ellipsis-vertical'></i>
        </button>
    ";

    // Bubble
    echo "<div class='message-bubble'>";

    if ($msg["deleted"]) {
        echo "<div class='deleted-text'>🗑️ <i>This message was deleted</i></div>";
    } else {
        echo "<div class='msg-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</div>";
    }

    echo "</div>"; // bubble

    // Edited tag
    if ($msg["edited"] && !$msg["deleted"]) {
        echo "<div class='edited-label'>(edited)</div>";
    }

    // Timestamp
    echo "<div class='message-time'>$timeText</div>";

    echo "</div></div>";
}

?>
