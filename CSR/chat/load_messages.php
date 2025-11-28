<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;

if (!$client_id) {
    echo "No client selected.";
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT c.id, c.sender_type, c.message, c.created_at,
               cm.media_path, cm.media_type
        FROM chat c
        LEFT JOIN chat_media cm ON cm.chat_id = c.id
        WHERE c.client_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$client_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "<p style='text-align:center;color:gray;'>No messages yet.</p>";
        exit;
    }

    foreach ($rows as $row) {
        $sender = ($row["sender_type"] === "csr") ? "sent" : "received";

        echo "<div class='message {$sender}'>
            <div class='message-avatar'>
                <img src='../upload/default_avatar.png'>
            </div>
            <div>
                <div class='message-bubble'>";

        if (!empty($row["media_path"])) {
            if ($row["media_type"] === "image") {
                echo "<img src='../../{$row["media_path"]}' class='media-thumb'>";
            } else {
                echo "<a href='../../{$row["media_path"]}' download>📎 Download File</a>";
            }
        }

        if (!empty($row["message"])) {
            echo nl2br(htmlspecialchars($row["message"]));
        }

        echo "</div>
                <div class='message-time'>" . date("M j g:i A", strtotime($row["created_at"])) . "</div>
            </div>
        </div>";
    }

} catch (Throwable $e) {
    echo "DB Error: " . $e->getMessage();
}
