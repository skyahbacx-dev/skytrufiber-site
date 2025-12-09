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
   HELPERS — FETCH LOG TIMES
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
    echo "<p style='text-align:center;color:#888;padding:10px;'>No messages yet.</p>";
    exit;
}

/* ============================================================
   DIVIDERS — HTML ONCE ONLY
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

$printedPending  = false;
$printedResolved = false;

/* ============================================================
   RENDER CHAT MESSAGES
============================================================ */
foreach ($rows as $msg) {

    $id        = $msg["id"];
    $senderRaw = $msg["sender_type"];
    $msgTime   = strtotime($msg["created_at"]);
    $timeFmt   = date("M j g:i A", $msgTime);

    // Divider: PENDING
    if ($pendingAt && !$printedPending && $msgTime > strtotime($pendingAt)) {
        echo $pendingDividerHTML;
        $printedPending = true;
    }

    // Divider: RESOLVED
    if ($resolvedAt && !$printedResolved && $msgTime > strtotime($resolvedAt)) {
        echo $resolvedDividerHTML;
        $printedResolved = true;
    }

    /* ============================================================
       CHOOSE MESSAGE SIDE — CSR = sent (right), client = received (left)
    ============================================================ */
    $side = ($senderRaw === "csr") ? "sent" : "received";

    echo "<div class='message $side' data-msg-id='$id'>";

    /* ============================================================
       AVATAR — show only for received messages
    ============================================================ */
    if ($side === "received") {
        echo "
            <div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
            </div>
        ";
    } else {
        // keep placeholder spacing on CSR messages
        echo "<div class='message-avatar'></div>";
    }

    echo "<div class='message-content'>";

    /* ============================================================
       ACTION MENU BUTTON (CSR can edit only CSR messages)
    ============================================================ */
    if ($senderRaw === "csr" && !$msg["deleted"]) {
        echo "
            <button class='more-btn' data-id='$id'>
                <i class='fa-solid fa-ellipsis-vertical'></i>
            </button>
        ";
    }

    /* ============================================================
       MESSAGE BUBBLE
    ============================================================ */
    echo "<div class='message-bubble'>";

    if ($msg["deleted"]) {
        echo "<div class='deleted-text'>🗑️ <i>This message was deleted</i></div>";
    } else {
        echo "<div class='msg-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</div>";
    }

    echo "</div>"; // bubble end

    /* ============================================================
       EDITED LABEL
    ============================================================ */
    if ($msg["edited"] && !$msg["deleted"]) {
        echo "<div class='edited-label'>(edited)</div>";
    }

    /* ============================================================
       TIMESTAMP
    ============================================================ */
    echo "<div class='message-time'>$timeFmt</div>";

    echo "</div></div>"; // content + message wrapper end
}
?>
