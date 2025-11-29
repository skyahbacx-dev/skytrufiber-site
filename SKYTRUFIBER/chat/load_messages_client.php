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
        $sender    = ($msg["sender_type"] === "csr") ? "received" : "sent";
        $timestamp = date("M j g:i A", strtotime($msg["created_at"]));

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        echo "<div class='message-bubble'>";

        // Load media
        $mediaStmt = $conn->prepare("
            SELECT id, media_type 
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($mediaList && count($mediaList) > 1) {
            echo "<div class='carousel-container'>";
            foreach ($mediaList as $m) {
                $mediaID = (int)$m["id"];
                $filePath = "../chat/get_media.php?id=$mediaID";

                if ($m["media_type"] === "image") {
                    echo "<div class='media-wrapper'>
                            <img src='$filePath' class='media-thumb carousel-img'>
                            <button class='download-btn' onclick=\"window.open('$filePath')\">⬇</button>
                          </div>";
                } elseif ($m["media_type"] === "video") {
                    echo "<div class='media-wrapper'>
                            <video class='carousel-video media-thumb' autoplay muted loop controls>
                                <source src='$filePath'>
                            </video>
                            <button class='download-btn' onclick=\"window.open('$filePath')\">⬇</button>
                          </div>";
                } else {
                    echo "<a href='$filePath' download class='download-btn'>📎 File</a>";
                }
            }
            echo "</div>";
        }
        elseif ($mediaList && count($mediaList) === 1) {
            $media = $mediaList[0];
            $filePath = "../chat/get_media.php?id=".(int)$media["id"];

            echo "<div class='media-wrapper'>";
            if ($media["media_type"] === "image") {
                echo "<img src='$filePath' class='media-thumb'>";
            } elseif ($media["media_type"] === "video") {
                echo "<video autoplay muted loop controls class='media-video'>
                        <source src='$filePath'>
                      </video>";
            } else {
                echo "<a href='$filePath' download class='download-btn'>📎 Download</a>";
            }
            echo "<button class='download-btn' onclick=\"window.open('$filePath')\">⬇</button>";
            echo "</div>";
        }

        if (!empty($msg["message"])) {
            echo "<div class='msg-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</div>";
        }

        echo "</div>";
        echo "<div class='message-time'>$timestamp</div>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
