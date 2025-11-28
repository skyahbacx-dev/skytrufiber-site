<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;

if (!$client_id) {
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

    foreach ($rows as $row) {

        $msgID = (int)$row["id"];
        $sender = ($row["sender_type"] === "csr") ? "sent" : "received";
        $timestamp = date("M j g:i A", strtotime($row["created_at"]));

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        // AVATAR
        echo "<div class='message-avatar'>
                <img src='/upload/default_avatar.png' alt='avatar'>
              </div>";

        echo "<div class='message-content'>";
        echo "<div class='message-bubble'>";

        // MEDIA HANDLING
        if (!empty($row["media_path"])) {

            $filePath = "/" . $row["media_path"];

            if ($row["media_type"] === "image") {
                echo "<img src='$filePath' class='media-thumb' />";
            }
            elseif ($row["media_type"] === "video") {
                echo "<video controls class='media-video'>
                        <source src='$filePath' type='video/mp4'>
                        Your browser does not support videos.
                      </video>";
            }
            else {
                echo "<a href='$filePath' download class='download-btn'>📎 Download File</a>";
            }
        }

        // TEXT
        if (!empty($row["message"])) {
            echo nl2br(htmlspecialchars($row["message"]));
        }

        echo "</div>"; // bubble
        echo "<div class='message-time'>$timestamp</div>";
        echo "</div>"; // message-content
        echo "</div>"; // wrapper
    }

} catch (Exception $e) {
    echo "<p style='color:red'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
