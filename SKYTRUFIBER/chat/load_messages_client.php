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
    $stmt = $conn->prepare("
        SELECT id, sender_type, message, created_at
        FROM chat
        WHERE client_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$client_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as $msg) {
        $msgID     = (int)$msg["id"];
        $sender    = ($msg["sender_type"] === "csr") ? "received" : "sent";
        $timestamp = date("M j g:i A", strtotime($msg["created_at"]));

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
              </div>";

        echo "<div class='message-content'>";
        echo "<div class='message-bubble'>";

        // Load media
        $mediaStmt = $conn->prepare("SELECT id, media_type FROM chat_media WHERE chat_id = ?");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        // multiple media
        if ($mediaList && count($mediaList) > 1) {
            echo "<div class='carousel-container'>";
            foreach ($mediaList as $m) {
                $mediaID = (int)$m["id"];
                $src = "get_media_client.php?id=$mediaID";

                if ($m["media_type"] === "image") {
                    echo "<img src='$src' class='carousel-img media-thumb'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video controls autoplay loop muted class='carousel-video'>
                            <source src='$src' type='video/mp4'>
                          </video>";
                } else {
                    echo "<a href='$src' download class='download-btn'>📎 File</a>";
                }
            }
            echo "</div>";
        }
        // single media
        elseif ($mediaList && count($mediaList) === 1) {
            $m = $mediaList[0];
            $src = "get_media_client.php?id=" . $m["id"];

            if ($m["media_type"] === "image") {
                echo "<img src='$src' class='media-thumb'>";
            } elseif ($m["media_type"] === "video") {
                echo "<video controls autoplay loop muted class='media-video'>
                        <source src='$src' type='video/mp4'>
                      </video>";
            } else {
                echo "<a href='$src' download class='download-btn'>📎 Download</a>";
            }
        }

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
