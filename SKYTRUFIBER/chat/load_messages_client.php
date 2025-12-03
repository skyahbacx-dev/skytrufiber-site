<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");
error_reporting(E_ALL);


if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

ini_set("display_errors", 0);

$username = $_POST["username"] ?? null;
if (!$username) exit("");

// Lookup user (email OR full name)
$stmt = $conn->prepare("
    SELECT id, full_name 
    FROM users
    WHERE email = ? COLLATE utf8mb4_general_ci
       OR full_name = ? COLLATE utf8mb4_general_ci
    LIMIT 1
");
$stmt->execute([$username, $username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) exit("");

$client_id = (int)$client["id"];

// Fetch ALL messages sorted correctly
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = ?
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$messages) exit("");

// Preload media (optimization)
$media_stmt = $conn->prepare("
    SELECT id, chat_id, media_type
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

    if ($msg["deleted"] == 1) {
        echo "<span class='removed-text'>Message removed</span>";
    } else {

        // Load media
        $media_stmt->execute([$msgID]);
        $media = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($media) {
            if (count($media) > 1) echo "<div class='carousel-container'>";

            foreach ($media as $m) {
                $id = $m["id"];
                $file = "get_media_client.php?id=$id";
                $thumb = "get_media_client.php?id=$id&thumb=1";

                if ($m["media_type"] === "image") {
                    echo "<img src='$thumb' data-full='$file' class='media-thumb'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video muted preload='metadata' data-full='$file' class='media-video'>
                            <source src='$thumb' type='video/mp4'>
                          </video>";
                } else {
                    echo "<a href='$file' download class='file-link'>📎 Download</a>";
                }
            }

            if (count($media) > 1) echo "</div>";
        }

        // Actual text
        if (trim($msg["message"]) !== "")
            echo nl2br(htmlspecialchars($msg["message"]));
    }

    echo "</div>"; // bubble

    echo "<div class='message-time'>$time";
    if ($msg["edited"]) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // Reactions
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

    // Action toolbar (Edit/Unsend/Delete)
    echo "<div class='action-toolbar'>
            <button class='react-btn' data-msg-id='$msgID'>😊</button>";

    if ($sender === "sent" && $msg["deleted"] == 0) {
        echo "<button class='more-btn' data-id='$msgID'>⋯</button>";
    }

    echo "</div>";

    echo "</div></div>";
}
?>
