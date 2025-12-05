<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

try {

    // Fetch all messages
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

    // GLOBAL MEDIA LIST INDEX
    $globalMediaIndex = 0;

    foreach ($messages as $msg) {

        $msgID = (int)$msg["id"];
        $sender = ($msg["sender_type"] === "csr") ? "sent" : "received";
        $timestamp = date("M j g:i A", strtotime($msg["created_at"]));

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        echo "<div class='message-avatar'><img src='/upload/default-avatar.png'></div>";
        echo "<div class='message-content'>";

        // ACTION BUTTON
        echo "<button class='more-btn' data-id='$msgID'><i class='fa-solid fa-ellipsis-vertical'></i></button>";

        echo "<div class='message-bubble'>";

        // FETCH MEDIA FOR MESSAGE
        $mquery = $conn->prepare("SELECT id, media_type FROM chat_media WHERE chat_id = ?");
        $mquery->execute([$msgID]);
        $mediaList = $mquery->fetchAll(PDO::FETCH_ASSOC);

        $count = count($mediaList);

        if ($count > 0) {
            echo "<div class='media-grid'>";

            foreach ($mediaList as $media) {
                $mediaID = (int)$media["id"];
                $src = "../chat/get_media.php?id=$mediaID";

                // IMPORTANT: Add global index for carousel
                $indexAttr = "data-media-index='{$globalMediaIndex}'";
                $globalMediaIndex++;

                echo "<div class='media-item'>";

                if ($media["media_type"] === "image") {
                    echo "<img src='$src' class='fullview-item' data-full='$src' $indexAttr>";
                }
                elseif ($media["media_type"] === "video") {
                    echo "<video class='fullview-item' data-full='$src' $indexAttr muted>
                            <source src='$src' type='video/mp4'>
                          </video>";
                }

                echo "</div>";
            }

            echo "</div>";
        }

        if (!empty($msg["message"])) {
            $safe = nl2br(htmlspecialchars($msg["message"]));
            echo "<div class='msg-text'>$safe</div>";
        }

        echo "</div>";
        echo "<div class='message-time'>$timestamp</div></div>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
