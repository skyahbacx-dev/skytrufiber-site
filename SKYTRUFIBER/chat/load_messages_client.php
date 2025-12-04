<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");
error_reporting(E_ALL);

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
if (!$username) exit("");

// FIX: PostgreSQL does NOT support utf8mb4_general_ci
// Remove collation entirely so both email + fullname match
$stmt = $conn->prepare("
    SELECT id, full_name 
    FROM users
    WHERE email = ?
       OR full_name = ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) exit("");

$client_id = $user["id"];

// Fetch messages
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = ?
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Media loader
$media_stmt = $conn->prepare("
    SELECT id, media_type 
    FROM chat_media
    WHERE chat_id = ?
");
foreach ($messages as $msg) {

    $msgID = $msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $time   = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$msgID'>
            <div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
            </div>
            <div class='message-content'>
                <div class='message-bubble'>";

    if ($msg["deleted"] == 1) {
        echo "<span class='removed-text'>Message removed</span>";
    } else {

        // LOAD MEDIA
        $media_stmt->execute([$msgID]);
        $media = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($media) {

            // Messenger-style grid: 1,2,3,4 photos
            $count = count($media);

            if ($count > 1) echo "<div class='media-grid grid-$count'>";

            foreach ($media as $m) {

                $id = $m["id"];
                $full = "get_media_client.php?id=$id";
                $thumb = "get_media_client.php?id=$id&thumb=1";

                if ($m["media_type"] === "image") {
                    echo "<img src='$thumb' data-full='$full' class='media-thumb'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video muted preload='metadata' data-full='$full' class='media-video'>
                            <source src='$thumb' type='video/mp4'>
                          </video>";
                }
            }

            if ($count > 1) echo "</div>";
        }

        // TEXT
        if (trim($msg["message"]) !== "")
            echo nl2br(htmlspecialchars($msg["message"]));
    }

    echo "</div>"; // message-bubble

    // TIME
    echo "<div class='message-time'>$time";
    if ($msg["edited"]) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // REACTIONS
    $r = $conn->prepare("
        SELECT emoji, COUNT(*) AS total
        FROM chat_reactions
        WHERE chat_id = ?
        GROUP BY emoji
        ORDER BY total DESC
    ");
    $r->execute([$msgID]);
    $reactions = $r->fetchAll(PDO::FETCH_ASSOC);

    echo "<div class='reaction-bar'>";
    foreach ($reactions as $rc) {
        echo "<span class='reaction-item'>{$rc['emoji']} <span class='reaction-count'>{$rc['total']}</span></span>";
    }
    echo "</div>";

    // ACTION BUTTONS
    echo "<div class='msg-actions'>
            <button class='react-btn' data-msg-id='$msgID'>☺︎</button>";

    if ($sender === "sent" && $msg["deleted"] == 0)
        echo "<button class='more-btn' data-id='$msgID'>⋯</button>";

    echo "</div>"; // msg-actions

    echo "</div></div>"; // message-content + message
}
?>
