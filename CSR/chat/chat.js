<?php
require_once "../../db_connect.php";

$client_id = $_GET["client_id"] ?? null;
if (!$client_id) {
    echo "No client ID provided";
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT chat.id, chat.sender_type, chat.message, chat.created_at,
               cm.media_path, cm.media_type
        FROM chat
        LEFT JOIN chat_media cm ON cm.chat_id = chat.id
        WHERE chat.client_id = ?
        ORDER BY chat.created_at ASC
    ");
    $stmt->execute([$client_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as $msg) {
        $class = ($msg["sender_type"] === "csr") ? "csr-message" : "client-message";

        echo "<div class='chat-bubble $class'>";

        // If text exists
        if (!empty($msg["message"])) {
            echo "<p class='chat-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</p>";
        }

        // If media exists
        if (!empty($msg["media_path"])) {
            $mediaPath = "../../" . htmlspecialchars($msg["media_path"]);
            $mediaType = $msg["media_type"];

            if ($mediaType === "image") {
                echo "
                <img src='$mediaPath' class='chat-image-preview' onclick='openImageModal(\"$mediaPath\")'>
                ";
            } elseif ($mediaType === "video") {
                echo "
                <video class='chat-video-preview' controls>
                    <source src='$mediaPath' type='video/mp4'>
                </video>
                ";
            } else {
                echo "
                <a href='$mediaPath' class='chat-file-download' download>
                    <i class='fa fa-download'></i> Download File
                </a>
                ";
            }
        }

        $time = date("M d h:i A", strtotime($msg["created_at"]));
        echo "<div class='timestamp'>$time</div>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
?>
