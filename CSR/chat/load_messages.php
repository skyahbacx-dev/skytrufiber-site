<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

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

foreach ($rows as $msg) {

    $msgID = $msg["id"];
    $sender = $msg["sender_type"] === "csr" ? "sent" : "received";
    $timestamp = date("M j g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$msgID'>";

    echo "
        <div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
        </div>
    ";

    echo "<div class='message-content'>";

    echo "
        <button class='more-btn' data-id='$msgID'>
            <i class='fa-solid fa-ellipsis-vertical'></i>
        </button>
    ";

    echo "<div class='message-bubble'>";

    if ($msg["deleted"]) {
        echo "<div class='deleted-text'>🗑️ <i>This message was deleted</i></div>";
    } else {
        echo "<div class='msg-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</div>";
    }

    echo "</div>"; // bubble

    if ($msg["edited"] && !$msg["deleted"]) {
        echo "<div class='edited-label'>(edited)</div>";
    }

    echo "<div class='message-time'>$timestamp</div>";

    echo "</div></div>";
}
?>
