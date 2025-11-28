<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST['client_id'] ?? null;

if (!$client_id) {
    echo "Missing client ID";
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

        $sender = ($row["sender_type"] === "csr") ? "sent" : "received";

        echo "<div class='message $sender'>";

        // Avatar
        echo "
            <div class='message-avatar'>
                <img src='/upload/default_avatar.png' alt='avatar'>
            </div>
        ";

        echo "<div>"; // message wrapper
        echo "<div class='message-bubble'>"; // bubble

        // If media exists
        if (!empty($row["media_path"])) {

            $filePath = "/" . $row["media_path"];

            if ($row["media_type"] === "image") {
                echo "<img src='$filePath' class='media-thumb' onclick='openLightbox(\"$filePath\")'>";
            } else {
                echo "<a class='download-btn' href='$filePath' download>📎 Download File</a>";
            }
        }

        // If message text exists
        if (!empty($row["message"])) {
            echo nl2br(htmlspecialchars($row["message"]));
        }

        echo "</div>"; // close bubble

        echo "<div class='message-time'>" . date("M j g:i A", strtotime($row["created_at"])) . "</div>";

        echo "</div>"; // close wrapper
        echo "</div>"; // close message container
    }

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
?>
