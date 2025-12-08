<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

try {

    /* ============================================================
       1) Fetch all TEXT messages
    ============================================================ */
    $stmt = $conn->prepare("
        SELECT id, sender_type, message, deleted, created_at, edited
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
       2) RENDER MESSAGES (TEXT ONLY + DELETE PLACEHOLDER)
    ============================================================ */
    foreach ($messages as $msg) {

        $msgID     = (int)$msg["id"];
        $sender    = ($msg["sender_type"] === "csr") ? "sent" : "received";
        $timestamp = date("M j g:i A", strtotime($msg["created_at"]));
        $isDeleted = (bool)$msg["deleted"];

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        // Avatar
        echo "
            <div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
            </div>
        ";

        echo "<div class='message-content'>";

        // Menu button (still allowed for CSR to delete/edit their messages)
        echo "
            <button class='more-btn' data-id='$msgID'>
                <i class='fa-solid fa-ellipsis-vertical'></i>
            </button>
        ";

        echo "<div class='message-bubble'>";

        /* ============================================================
           3) DELETE PLACEHOLDER
        ============================================================ */
        if ($isDeleted) {
            echo "
                <div class='msg-text deleted-text'>
                    🗑️ <i>This message was deleted</i>
                </div>
            ";

        } else {
            /* Normal visible message */
            $safe = nl2br(htmlspecialchars($msg["message"]));
            echo "<div class='msg-text'>$safe</div>";
        }

        echo "</div>"; // /message-bubble

        /* Edited Label */
        if (!$isDeleted && !empty($msg["edited"])) {
            echo "<div class='edited-label'>(edited)</div>";
        }

        /* Timestamp */
        echo "<div class='message-time'>$timestamp</div>";

        echo "</div>"; // /message-content
        echo "</div>"; // /message wrapper
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
