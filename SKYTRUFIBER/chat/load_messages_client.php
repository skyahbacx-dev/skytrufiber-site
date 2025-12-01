<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;
if (!$username) exit;

$stmt = $conn->prepare("
    SELECT id FROM users WHERE email = ?
");
$stmt->execute([$username]);
$clientRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clientRow) exit("User not found");

$client_id = $clientRow["id"];

try {
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
        echo "<div class='message-content'><div class='message-bubble'>";

        // MEDIA
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
                $filePath = "get_media_client.php?id=" . (int)$m["id"];
                if ($m["media_type"] === "image") {
                    echo "<img src='$filePath' class='carousel-img media-thumb'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video autoplay loop muted controls class='carousel-video'>
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
            $filePath = "get_media_client.php?id=" . (int)$media["id"];

            if ($media["media_type"] === "image") {
                echo "<img src='$filePath' class='media-thumb'>";
            } elseif ($media["media_type"] === "video") {
                echo "<video autoplay loop muted controls class='media-video'>
                        <source src='$filePath' type='video/mp4'>
                      </video>";
            } else {
                echo "<a href='$filePath' download class='download-btn'>📎 Download File</a>";
            }
        }

        if (!empty($msg["message"])) echo nl2br(htmlspecialchars($msg["message"]));

        echo "</div>";
        echo "<div class='message-time'>$timestamp</div>";
        echo "</div></div>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
