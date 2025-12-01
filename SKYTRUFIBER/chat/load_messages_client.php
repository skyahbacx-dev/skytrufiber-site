<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;
if (!$username) exit("No username");

// Find client by email or name
$stmt = $conn->prepare("
    SELECT id, full_name
    FROM users
    WHERE email = ? OR full_name = ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$clientRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clientRow) exit("User not found");

$client_id = $clientRow["id"];

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

// Avatar images
$csrAvatar  = "/upload/default-avatar.png";
$userAvatar = "/upload/default-avatar.png";

foreach ($messages as $msg) {

    $msgID     = (int)$msg["id"];
    $sender    = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $avatar    = ($sender === "received") ? $csrAvatar : $userAvatar;
    $timestamp = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$msgID'>";

    echo "<div class='message-avatar'>
            <img src='$avatar' alt='avatar'>
          </div>";

    echo "<div class='message-content'>
            <div class='message-bubble'>";

    // MEDIA BLOCK
    $mediaStmt = $conn->prepare("SELECT id, media_type FROM chat_media WHERE chat_id = ?");
    $mediaStmt->execute([$msgID]);
    $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($mediaList) {

        if (count($mediaList) > 1) echo "<div class='carousel-container'>";

        foreach ($mediaList as $m) {
            $mediaID = (int)$m["id"];
            $filePath = "get_media_client.php?id=$mediaID"; // full
            $thumbPath = "get_media_client.php?id=$mediaID&thumb=1"; // lightweight preview

            if ($m["media_type"] === "image") {
                echo "<img src='$thumbPath' data-full='$filePath' class='media-thumb'>";
            }
            elseif ($m["media_type"] === "video") {
                echo "<video muted preload='metadata' data-full='$filePath' class='media-video'>
                        <source src='$filePath' type='video/mp4'>
                      </video>";
            }
            else {
                echo "<a href='$filePath' download>📎 Download File</a>";
            }
        }

        if (count($mediaList) > 1) echo "</div>";
    }

    if (!empty($msg["message"])) echo nl2br(htmlspecialchars($msg["message"]));

    echo "</div>";
    echo "<div class='message-time'>$timestamp</div>";
    echo "</div></div>";
}
?>
