<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");
error_reporting(E_ALL);

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;
if (!$username) exit("");

// Lookup user (PostgreSQL ILIKE = case-insensitive)
$stmt = $conn->prepare("
    SELECT id, full_name 
    FROM users
    WHERE email ILIKE ? OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) exit("");

$client_id = (int)$client["id"];

// Fetch messages
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = ?
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$messages) exit("");

// Prepare media preload statement
$media_stmt = $conn->prepare("
    SELECT id, media_type
    FROM chat_media
    WHERE chat_id = ?
");

foreach ($messages as $msg) {

    $msgID = $msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $time   = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$msgID'>";

    echo "<div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
          </div>";

    echo "<div class='message-content'>
            <div class='message-bubble'>";

    // Removed message
    if ($msg["deleted"] == 1) {
        echo "<span class='removed-text'>Message removed</span>";
    } else {

        // LOAD MEDIA
        $media_stmt->execute([$msgID]);
        $media = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($media) {
            echo "<div class='carousel-container'>";
            foreach ($media as $m) {
                $file  = "get_media_client.php?id={$m['id']}";
                $thumb = "get_media_client.php?id={$m['id']}&thumb=1";

                if ($m["media_type"] === "image") {
                    echo "<img src='$thumb' data-full='$file' class='media-thumb'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video muted preload='metadata' data-full='$file' class='media-video'>
                            <source src='$thumb' type='video/mp4'>
                          </video>";
                }
            }
            echo "</div>";
        }

        // TEXT
        if (trim($msg["message"]) !== "") {
            echo nl2br(htmlspecialchars($msg["message"]));
        }
    }

    echo "</div>"; // bubble

    // Message time + edited
    echo "<div class='message-time'>$time";
    if ($msg["edited"]) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // LOAD REACTIONS
    $r = $conn->prepare("
        SELECT emoji, COUNT(*) AS total
        FROM chat_reactions
        WHERE chat_id = ?
        GROUP BY emoji
        ORDER BY total DESC
    ");
    $r->execute([$msgID]);
    $reactions = $r->fetchAll(PDO::FETCH_ASSOC);

    if ($reactions) {
        echo "<div class='reaction-bar'>";
        foreach ($reactions as $rc) {
            echo "<span class='reaction-item'>{$rc['emoji']} <span class='reaction-count'>{$rc['total']}</span></span>";
        }
        echo "</div>";
    }

    // Reaction + More button
    echo "<div class='action-toolbar'>
            <button class='react-btn' data-msg-id='$msgID'>☺︎</button>";

    if ($sender === "sent" && $msg["deleted"] == 0) {
        echo "<button class='more-btn' data-id='$msgID'>⋯</button>";
    }

    echo "</div></div></div>";
}
?>
