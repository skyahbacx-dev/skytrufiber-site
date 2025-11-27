<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;

if (!$client_id) {
    echo "No client selected";
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT c.id, c.sender_type, c.message, c.created_at,
        (SELECT media_path FROM chat_media WHERE chat_id = c.id LIMIT 1) AS media_path,
        (SELECT media_type FROM chat_media WHERE chat_id = c.id LIMIT 1) AS media_type
        FROM chat c
        WHERE c.client_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$client_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as $m) {
        $isCSR = ($m["sender_type"] === "csr");
        $bubbleClass = $isCSR ? "msg-csr" : "msg-client";

        echo "<div class='chat-bubble $bubbleClass'>";

        // TEXT MESSAGE
        if (!empty($m["message"])) {
            echo nl2br(htmlspecialchars($m["message"]));
        }

        // MEDIA THUMBNAILS
        if ($m["media_path"]) {
            $path = "../../" . $m["media_path"];
            $isImage = in_array($m["media_type"], ["image"]);
            $isVideo = in_array($m["media_type"], ["video"]);

            if ($isImage) {
                echo "<img src='$path' class='chat-img' onclick='openMediaViewer(\"$path\")'>";
            } else if ($isVideo) {
                echo "<video class='chat-video' controls><source src='$path'></video>";
            } else {
                echo "<a href='$path' download class='chat-file-preview'>
                        <i class='fa fa-file'></i> Download File
                      </a>";
            }
        }

        echo "<span class='chat-time'>" . date("M d g:i A", strtotime($m["created_at"])) . "</span>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
?>
