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
                <img src='/upload/default_avatar.png' alt='avatar'>
              </div>";

        echo "<div class='message-content'>";
        echo "<div class='message-bubble'>";

        // Fetch associated media
        $mediaStmt = $conn->prepare("
            SELECT media_path, media_type
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        // ==========================
        // MULTIPLE MEDIA (Carousel)
        // ==========================
        if ($mediaList && count($mediaList) > 1) {
            echo "<div class='carousel-container'>";

            foreach ($mediaList as $m) {

                // Access through get_media.php (required for /tmp storage on Railway)
                $filePath = "/chat/get_media.php?file=" . urlencode($m["media_path"]);

                if ($m["media_type"] === "image") {
                    echo "<img src='$filePath' class='carousel-img media-thumb'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video controls class='carousel-video'>
                            <source src='$filePath' type='video/mp4'>
                          </video>";
                } else {
                    echo "<a href='$filePath' download class='download-btn'>📎 File</a>";
                }
            }

            echo "</div>";
        }

        // ==========================
        // ONE MEDIA ITEM
        // ==========================
        elseif ($mediaList && count($mediaList) === 1) {

            $media = $mediaList[0];
            $filePath = "/CSR/chat/get_media.php?file=" . urlencode($media["media_path"]);

            if ($media["media_type"] === "image") {
                echo "<img src='$filePath' class='media-thumb'>";
            } elseif ($media["media_type"] === "video") {
                echo "<video controls class='media-video'>
                        <source src='$filePath' type='video/mp4'>
                      </video>";
            } else {
                echo "<a href='$filePath' download class='download-btn'>📎 Download File</a>";
            }
        }

        // Display text content
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
