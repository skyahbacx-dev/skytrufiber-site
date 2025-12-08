<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

// ============================================================
// GET CLIENT & TICKET STATUS
// ============================================================
$stmt = $conn->prepare("
    SELECT ticket_status
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$client_id]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

$ticketStatus = $client["ticket_status"] ?? "unresolved";

// ============================================================
// GET RESOLVED TIMESTAMP (from ticket_logs table)
// ============================================================
$resolvedAt = null;

if ($ticketStatus === "resolved") {
    $log = $conn->prepare("
        SELECT changed_at
        FROM ticket_logs
        WHERE client_id = ?
          AND new_status = 'resolved'
        ORDER BY changed_at ASC
        LIMIT 1
    ");
    $log->execute([$client_id]);
    $row = $log->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $resolvedAt = $row["changed_at"];
    }
}

// ============================================================
// FETCH ALL MESSAGES
// ============================================================
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

// ============================================================
// PREPARE DIVIDER HTML (SHOWN ONLY ONCE AFTER RESOLUTION)
// ============================================================
$dividerHtml = "";
$dividerPrinted = false;

if ($ticketStatus === "resolved" && $resolvedAt) {
    $dividerHtml = "
        <div class='system-divider'>
            <span>Ticket marked as <strong>RESOLVED</strong> on " .
                date("M j, Y g:i A", strtotime($resolvedAt)) .
            "</span>
        </div>
    ";
}

// ============================================================
// RENDER MESSAGES
// ============================================================
foreach ($rows as $msg) {

    // Print the divider when reaching messages AFTER resolved timestamp
    if (
        !$dividerPrinted &&
        $ticketStatus === "resolved" &&
        $resolvedAt &&
        strtotime($msg["created_at"]) > strtotime($resolvedAt)
    ) {
        echo $dividerHtml;
        $dividerPrinted = true;
    }

    $msgID = $msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "sent" : "received";
    $timestamp = date("M j g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$msgID'>";

    // Avatar
    echo "
        <div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
        </div>
    ";

    echo "<div class='message-content'>";

    // More button (edit/delete)
    echo "
        <button class='more-btn' data-id='$msgID'>
            <i class='fa-solid fa-ellipsis-vertical'></i>
        </button>
    ";

    // Bubble content
    echo "<div class='message-bubble'>";

    if ($msg["deleted"]) {
        echo "<div class='deleted-text'>🗑️ <i>This message was deleted</i></div>";
    } else {
        echo "<div class='msg-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</div>";
    }

    echo "</div>"; // end bubble

    if ($msg["edited"] && !$msg["deleted"]) {
        echo "<div class='edited-label'>(edited)</div>";
    }

    echo "<div class='message-time'>$timestamp</div>";

    echo "</div></div>";
}

?>
