<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

// --------------------------------------------------
// FETCH ALL CHAT MESSAGES FOR THIS CLIENT
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT 
        id,
        sender_type,
        message,
        deleted,
        edited,
        created_at
    FROM chat
    WHERE client_id = ?
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "<p style='text-align:center;color:#777;padding:10px;'>No messages yet.</p>";
    exit;
}

foreach ($rows as $msg) {

    $msgID  = $msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "sent" : "received"; 
    $time   = date("M j g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$msgID'>";

    // --------------------------------------------------
    // AVATAR (Only show avatar for client messages)
    // --------------------------------------------------
    if ($sender === "received") {
        echo "
            <div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
            </div>
        ";
    }

    echo "<div class='message-content'>";

    // --------------------------------------------------
    // ACTION MENU (CSR can edit/delete only *their* own messages)
    // --------------------------------------------------
    if ($sender === "sent" && !$msg["deleted"]) {
        echo "
            <div class='action-toolbar'>
                <button class='more-btn' data-id='$msgID'>⋯</button>
            </div>
        ";
    }

    // --------------------------------------------------
    // MESSAGE BUBBLE
    // --------------------------------------------------
    echo "<div class='message-bubble'>";

    if ($msg["deleted"]) {
        echo "<div class='deleted-text'><i>Message removed</i></div>";
    } else {
        $safeText = nl2br(htmlspecialchars($msg["message"]));
        echo "<div class='msg-text'>{$safeText}</div>";
    }

    echo "</div>"; // message-bubble

    // Edited label
    if ($msg["edited"] && !$msg["deleted"]) {
        echo "<div class='edited-label'>(edited)</div>";
    }

    // Timestamp
    echo "<div class='message-time'>$time</div>";

    echo "</div>"; // message-content
    echo "</div>"; // message wrapper
}
?>
