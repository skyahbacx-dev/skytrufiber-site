<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;
if (!$username) exit("No username");

// Lookup customer by email or full name
$stmt = $conn->prepare("
    SELECT id, full_name
    FROM users
    WHERE email = ? OR full_name = ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$clientRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clientRow) exit("User not found");

$client_id = (int)$clientRow["id"];

// Fetch chat messages
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted
    FROM chat
    WHERE client_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$messages) exit;

// Avatar placeholders
$csrAvatar  = "/upload/default-avatar.png";
$userAvatar = "/upload/default-avatar.png";

// Render messages
foreach ($messages as $msg) {

    $msgID     = (int)$msg["id"];
    $sender    = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $avatar    = ($sender === "received") ? $csrAvatar : $userAvatar;
    $timestamp = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender fadeup' data-msg-id='$msgID'>";

    echo "<div class='message-avatar'>
            <img src='$avatar' alt='avatar'>
          </div>";

    echo "<div class='message-content'>
            <div class='message-bubble'>";

    // =======================
    // If deleted: show removed placeholder
    // =======================
    if ($msg["deleted"] == 1) {
        echo "<span class='removed-text'>Message removed</span>";
    } else {

        // ===== Load media files =====
        $mediaStmt = $conn->prepare("
            SELECT id, media_type
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($mediaList) {

            if (count($mediaList) > 1) echo "<div class='carousel-container'>";

            foreach ($mediaList as $m) {

                $mediaID   = (int)$m["id"];
                $filePath  = "get_media_client.php?id=$mediaID";
                $thumbPath = "get_media_client.php?id=$mediaID&thumb=1";

                if ($m["media_type"] === "image") {
                    echo "<img src='$thumbPath' data-full='$filePath' class='media-thumb'>";
                }
                elseif ($m["media_type"] === "video") {
                    echo "<video muted preload='metadata' data-full='$filePath' class='media-video'>
                            <source src='$thumbPath' type='video/mp4'>
                          </video>";
                }
                else {
                    echo "<a href='$filePath' download class='file-link'>📎 Download File</a>";
                }
            }

            if (count($mediaList) > 1) echo "</div>";
        }

        // ===== Text =====
        if (!empty($msg["message"])) {
            echo nl2br(htmlspecialchars($msg["message"]));
        }
    }

    // Close bubble
    echo "</div>";

    // Timestamp
    echo "<div class='message-time'>$timestamp</div>";

    // Unsend button (CLIENT ONLY & not deleted)
    if ($sender === "sent" && $msg["deleted"] == 0) {
        echo "<button class='delete-btn' data-id='$msgID'>
                <i class='fa-solid fa-trash'></i>
              </button>";
    }

    echo "</div></div>";
}
?>
