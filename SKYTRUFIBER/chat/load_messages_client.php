<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;
if (!$username) exit;

// Get client ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) exit;
$client_id = (int)$client["id"];

try {
    // Load messages
    $stmt = $conn->prepare("
        SELECT id, sender_type, message, created_at
        FROM chat
        WHERE client_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$client_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$messages) exit;

    foreach ($messages as $msg) {

        $msgID     = (int)$msg["id"];
        $sender    = ($msg["sender_type"] === "csr") ? "received" : "sent";
        $timestamp = date("g:i A", strtotime($msg["created_at"]));

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        // Avatar aligned properly
        echo "<div class='message-avatar'>
                <img src=\"/upload/default-avatar.png\" alt='avatar'>
              </div>";

        echo "<div class='message-content'>";
        echo "<div class='message-bubble'>";

        // Load media list
        $mediaStmt = $conn->prepare("
            SELECT id, media_type 
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        // Multiple media (carousel)
        if ($mediaList && count($mediaList) > 1) {
            echo "<div class='carousel-container'>";
            foreach ($mediaList as $m) {
                $mediaID = (int)$m["id"];
                $filePath = "get_media_client.php?id=$mediaID";

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

        // One media file
        elseif ($mediaList && count($mediaList) === 1) {

            $m = $mediaList[0];
            $mediaID = (int)$m["id"];
            $filePath = "get_media_client.php?id=$mediaID";

            if ($m["media_type"] === "image") {
                echo "<img src='$filePath' class='media-thumb'>";
            } elseif ($m["media_type"] === "video") {
                echo "<video controls autoplay loop muted class='media-video'>
                        <source src='$filePath' type='video/mp4'>
                      </video>";
            } else {
                echo "<a href='$filePath' download class='download-btn'>📎 Download File</a>";
            }
        }

        // Text
        if (!empty($msg["message"])) {
            echo nl2br(htmlspecialchars($msg["message"]));
        }

        echo "</div>"; // bubble
        echo "<div class='message-time'>$timestamp</div>";
        echo "</div>"; // content
        echo "</div>"; // message wrapper
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
