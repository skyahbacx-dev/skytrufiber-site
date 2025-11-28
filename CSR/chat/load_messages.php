<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

try {

    // Fetch chat messages WITHOUT duplicating from media rows
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

        $msgID = (int)$msg["id"];
        $sender = ($msg["sender_type"] === "csr") ? "sent" : "received";
        $timestamp = date("M j g:i A", strtotime($msg["created_at"]));

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        // Avatar
        echo "<div class='message-avatar'>
                <img src='/upload/default_avatar.png'>
              </div>";

        echo "<div class='message-content'>";
        echo "<div class='message-bubble'>";

        // ===========================
        // FETCH MULTIPLE MEDIA FILES
        // ===========================
        $mediaStmt = $conn->prepare("
            SELECT media_path, media_type
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        // MULTIPLE FILE DISPLAY
        if ($mediaList && count($mediaList) > 1) {
            echo "<div class='carousel-container'>";
            foreach ($mediaList as $m) {
                $file = "/" . ltrim($m["media_path"], "/");

                if ($m["media_type"] === "image") {
                    echo "<img src='$file' class='carousel-img media-thumb'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video controls class='carousel-video'>
                            <source src='$file' type='video/mp4'>
                          </video>";
                } else {
                    echo "<a href='$file' download class='download-btn'>📎 $file</a>";
                }
            }
            echo "</div>";
        }

        // SINGLE FILE
        elseif ($mediaList && count($mediaList) === 1) {
            $file = "/" . ltrim($mediaList[0]["media_path"], "/");
            $type = $mediaList[0]["media_type"];

            if ($type === "image") {
                echo "<img src='$file' class='media-thumb'>";
            } elseif ($type === "video") {
                echo "<video controls class='media-video'>
                        <source src='$file' type='video/mp4'>
                      </video>";
            } else {
                echo "<a href='$file' download class='download-btn'>📎 Download File</a>";
            }
        }

        // TEXT MESSAGE CONTENT
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
