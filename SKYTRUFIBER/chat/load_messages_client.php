<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

try {

    // Fetch messages sorted oldest → newest
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

    // Avatar sources
    $csrAvatar  = "/upload/default-avatar.png";
    $userAvatar = "/upload/default-avatar.png";

    foreach ($messages as $msg) {

        $msgID     = (int)$msg["id"];
        $sender    = ($msg["sender_type"] === "csr") ? "sent" : "received"; // CSR = right bubble
        $avatar    = ($msg["sender_type"] === "csr") ? $csrAvatar : $userAvatar;
        $timestamp = date("g:i A", strtotime($msg["created_at"]));

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        // Avatar
        echo "<div class='message-avatar'>
                <img src='$avatar'>
              </div>";

        echo "<div class='message-content'>";
        echo "<div class='message-bubble'>";

        // MEDIA FETCH
        $mediaStmt = $conn->prepare("SELECT id, media_type FROM chat_media WHERE chat_id = ?");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        // MULTIPLE MEDIA (carousel style)
        if ($mediaList && count($mediaList) > 1) {
            echo "<div class='carousel-container'>";
            foreach ($mediaList as $m) {
                $filePath = "get_media_client.php?id=" . (int)$m["id"];

                if ($m["media_type"] === "image") {
                    echo "<img src='$filePath' class='carousel-img media-thumb'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video controls autoplay loop muted class='carousel-video'>
                            <source src='$filePath' type='video/mp4'>
                          </video>";
                } else {
                    echo "<a href='$filePath' download class='download-btn'>📎 File</a>";
                }
            }
            echo "</div>";
        }

        // SINGLE MEDIA
        elseif ($mediaList && count($mediaList) === 1) {
            $media = $mediaList[0];
            $filePath = "get_media_client.php?id=" . (int)$media["id"];

            if ($media["media_type"] === "image") {
                echo "<img src='$filePath' class='media-thumb'>";
            } elseif ($media["media_type"] === "video") {
                echo "<video controls autoplay loop muted class='media-video'>
                        <source src='$filePath' type='video/mp4'>
                      </video>";
            } else {
                echo "<a href='$filePath' download class='download-btn'>📎 Download File</a>";
            }
        }

        // TEXT MESSAGE
        if (!empty($msg["message"])) {
            echo nl2br(htmlspecialchars($msg["message"]));
        }

        echo "</div>";
        echo "<div class='message-time'>$timestamp</div>";
        echo "</div>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
