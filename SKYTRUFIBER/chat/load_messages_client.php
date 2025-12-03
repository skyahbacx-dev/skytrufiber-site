<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");
error_reporting(E_ALL);

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
if (!$username) exit("");

// Find client by email OR full name (PostgreSQL → NO COLLATION)
$stmt = $conn->prepare("
    SELECT id, full_name 
    FROM users
    WHERE email = $1
       OR full_name = $1
    LIMIT 1
");
$stmt->execute([$username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$client) exit("");

$client_id = (int)$client["id"];

// Fetch messages
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = $1
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$messages) exit("");

// Prepare media query
$media_stmt = $conn->prepare("
    SELECT id, media_type
    FROM chat_media
    WHERE chat_id = $1
");

foreach ($messages as $msg) {

    $msgID = $msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $time = date("g:i A", strtotime($msg["created_at"]));

    // Bubble Wrapper
    echo "<div class='message $sender' data-msg-id='$msgID'>";
    echo "<div class='message-avatar'><img src='/upload/default-avatar.png'></div>";
    echo "<div class='message-content'>";
    echo "<div class='message-bubble'>";

    // Deleted message
    if ($msg["deleted"]) {
        echo "<span class='removed-text'>Message removed</span>";
    } else {

        // Load media
        $media_stmt->execute([$msgID]);
        $media = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($media) {

            $multi = count($media) > 1;
            if ($multi) echo "<div class='carousel-container'>";

            foreach ($media as $m) {

                $mid = $m["id"];
                $full = "get_media_client.php?id=$mid";
                $thumb = "get_media_client.php?id=$mid&thumb=1";

                if ($m["media_type"] === "image") {
                    echo "<img src='$thumb' data-full='$full' class='media-thumb'>";
                } elseif ($m["media_type"] === "video") {
                    echo "<video muted preload='metadata' data-full='$full' class='media-video'>
                            <source src='$thumb' type='video/mp4'>
                          </video>";
                } else {
                    echo "<a href='$full' download>📎 File</a>";
                }
            }

            if ($multi) echo "</div>";
        }

        // Text
        if (trim($msg["message"]) !== "")
            echo nl2br(htmlspecialchars($msg["message"]));
    }

    echo "</div>"; // bubble

    // Time + edited
    echo "<div class='message-time'>$time";
    if ($msg["edited"]) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // Reaction BAR
    $r = $conn->prepare("
        SELECT emoji, COUNT(*) AS total
        FROM chat_reactions
        WHERE chat_id = $1
        GROUP BY emoji
        ORDER BY total DESC
    ");
    $r->execute([$msgID]);
    $reacts = $r->fetchAll(PDO::FETCH_ASSOC);

    if ($reacts) {
        echo "<div class='reaction-bar'>";
        foreach ($reacts as $rc) {
            echo "<span class='reaction-item'>{$rc['emoji']} <span class='reaction-count'>{$rc['total']}</span></span>";
        }
        echo "</div>";
    }

    // Toolbar
    echo "<div class='action-toolbar'>
            <button class='react-btn' data-msg-id='$msgID'>☺︎</button>";

    if ($sender === "sent" && !$msg["deleted"]) {
        echo "<button class='more-btn' data-id='$msgID'>⋯</button>";
    }

    echo "</div>";

    echo "</div></div>";
}
?>
