<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

try {

    /* ============================================================
       1) Fetch all messages (TEXT ONLY)
    ============================================================ */
    $stmt = $conn->prepare("
        SELECT id, sender_type, message, created_at, edited
        FROM chat
        WHERE client_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$client_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$messages) {
        echo "<p style='text-align:center;color:#777;padding:10px;'>No messages yet.</p>";
        exit;
    }

    /* ============================================================
       2) RENDER — no media, no grid, no thumbnails
    ============================================================ */
    foreach ($messages as $msg) {

        $msgID = (int)$msg["id"];
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

        // More button (edit/unsend/delete)
        echo "
            <button class='more-btn' data-id='$msgID'>
                <i class='fa-solid fa-ellipsis-vertical'></i>
            </button>
        ";

        /* ============================================================
           MESSAGE BUBBLE (text only)
        ============================================================ */
        echo "<div class='message-bubble'>";

        $text = htmlspecialchars($msg["message"]);
        $text = nl2br($text);

        echo "<div class='msg-text'>$text</div>";

        echo "</div>"; // close bubble

        /* Edited label */
        if (!empty($msg["edited"])) {
            echo "<div class='edited-label'>(edited)</div>";
        }

        /* Time */
        echo "<div class='message-time'>$timestamp</div>";

        echo "</div>"; // content
        echo "</div>"; // message wrapper
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
