<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_SESSION["user_id"] ?? null;   // client session id
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
        $sender    = ($msg["sender_type"] === "csr") ? "received" : "sent";  // Flip for UI
        $timestamp = date("g:i A", strtotime($msg["created_at"]));

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        echo "<div class='message-bubble'>";

        $mediaStmt = $conn->prepare("SELECT id, media_type FROM chat_media WHERE chat_id = ?");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($mediaList && count($mediaList) > 1) {
            echo "<div class='carousel-container'>";
            foreach ($mediaList as $m) {
                $mediaID = (int)$m["id"];
                $filePath = "../chat/get_media_client.php?id=$mediaID";

                if ($m["media_type"] === "image") {
                    echo "<img src='$filePath' class='carousel-img media-thumb'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video controls autoplay muted loop class='carousel-video'>
                            <source src='$filePath' type='video/mp4'>
                          </video>";
                } else {
                    echo "<a href='$filePath' download class='download-btn'>📎 File</a>";
                }
            }
            echo "</div>";
        }

        elseif ($mediaList && count($mediaList) === 1) {
            $media = $mediaList[0];
            $filePath = "../chat/get_media_client.php?id=" . (int)$media["id"];

            if ($media["media_type"] === "image") {
                echo "<img src='$filePath' class='media-thumb'>";
            } elseif ($media["media_type"] === "video") {
                echo "<video controls autoplay muted loop class='media-video'>
                        <source src='$filePath' type='video/mp4'>
                      </video>";
            } else {
                echo "<a href='$filePath' download class='download-btn'>📎 Download File</a>";
            }
        }

        if (!empty($msg["message"])) echo nl2br(htmlspecialchars($msg["message"]));

        echo "</div>";
        echo "<div class='message-time'>$timestamp</div>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
