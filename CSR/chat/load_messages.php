<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

try {

    // Fetch chat messages ordered chronologically
    $stmt = $conn->prepare("
        SELECT id, sender_type, message, created_at
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

    foreach ($messages as $msg) {

        $msgID     = (int)$msg["id"];
        $sender    = ($msg["sender_type"] === "csr") ? "sent" : "received";
        $timestamp = date("M j g:i A", strtotime($msg["created_at"]));

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        // Avatar
        echo "<div class='message-avatar'>
                <img src='../chat/default_avatar.png' alt='avatar'>
              </div>";

        echo "<div class='message-content'>";
        echo "<div class='message-bubble'>";

        // Fetch media
        $mediaStmt = $conn->prepare("
            SELECT media_path, media_type
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        // MULTIPLE FILES (Carousel)
        if ($mediaList && count($mediaList) > 1) {
            echo "<div class='carousel-container'>";

            foreach ($mediaList as $m) {
                $fileThumb = "/upload/chat_media/thumbs/" . $m["media_path"];
                $fileFull  = "/upload/chat_media/" . $m["media_path"];

                if ($m["media_type"] === "image") {
                    echo "<img src='$fileThumb' data-full='$fileFull'
                           class='carousel-img media-thumb' loading='lazy'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video controls class='carousel-video'>
                            <source src='$fileFull' type='video/mp4'>
                          </video>";
                } else {
                    echo "<a href='$fileFull' download class='download-btn'>📎 File</a>";
                }
            }

            echo "</div>";
        }

        // SINGLE FILE
        elseif ($mediaList && count($mediaList) === 1) {

            $media = $mediaList[0];
            $fileThumb = "/upload/chat_media/thumbs/" . $media["media_path"];
            $fileFull  = "/upload/chat_media/" . $media["media_path"];

            if ($media["media_type"] === "image") {
                echo "<img src='$fileThumb' data-full='$fileFull'
                       class='media-thumb' loading='lazy'>";
            } elseif ($media["media_type"] === "video") {
                echo "<video controls class='media-video'>
                        <source src='$fileFull' type='video/mp4'>
                      </video>";
            } else {
                echo "<a href='$fileFull' download class='download-btn'>📎 Download File</a>";
            }
        }

        // TEXT MESSAGE
        if (!empty($msg["message"])) {
            echo nl2br(htmlspecialchars($msg["message"]));
        }

        echo "</div>"; // bubble
        echo "<div class='message-time'>$timestamp</div>";
        echo "</div>"; // content
        echo "</div>"; // wrapper
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
