<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

try {

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

        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png' alt='avatar'>
              </div>";

        echo "<div class='message-content'>";
        echo "<div class='message-bubble'>";

        // Load media IDs and type
        $mediaStmt = $conn->prepare("
            SELECT id, media_type 
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        // =============================
        // MULTIPLE MEDIA CAROUSEL
        // =============================
        if ($mediaList && count($mediaList) > 1) {

            $groupID = "carousel-" . $msgID;

            echo "
            <div class='carousel-wrapper'>
                <button class='carousel-arrow left' data-group='$groupID'>&lsaquo;</button>
                <div class='carousel-container swipe-area' data-group='$groupID'>
            ";

            foreach ($mediaList as $m) {
                $mediaID = (int)$m["id"];
                $src = "../chat/get_media.php?id=$mediaID";

                if ($m["media_type"] === "image") {
                    echo "<img src='$src' class='carousel-img fullview-item'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video class='carousel-video fullview-item' autoplay muted loop>
                            <source src='$src' type='video/mp4'>
                          </video>";
                } else {
                    echo "<a href='$src' download class='download-btn'>📎 Download</a>";
                }
            }

            echo "
                </div>
                <button class='carousel-arrow right' data-group='$groupID'>&rsaquo;</button>
            </div>
            ";
        }

        // =============================
        // SINGLE MEDIA
        // =============================
        elseif ($mediaList && count($mediaList) === 1) {

            $media = $mediaList[0];
            $mediaID = (int)$media["id"];
            $src = "../chat/get_media.php?id=$mediaID";

            echo "<div class='single-media'>";

            if ($media["media_type"] === "image") {
                echo "<img src='$src' class='single-media-img fullview-item'>";
            } elseif ($media["media_type"] === "video") {
                echo "<video class='single-media-video fullview-item' autoplay muted loop controls>
                        <source src='$src' type='video/mp4'>
                      </video>";
            } else {
                echo "<a href='$src' download class='download-btn large'>📎 Download File</a>";
            }

            echo "</div>";
        }

        // =============================
        // TEXT MESSAGE
        // =============================
        if (!empty($msg["message"])) {
            echo nl2br(htmlspecialchars($msg["message"]));
        }

        echo "</div>"; // message bubble
        echo "<div class='message-time'>$timestamp</div>";
        echo "</div>"; // content
        echo "</div>"; // wrapper
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
