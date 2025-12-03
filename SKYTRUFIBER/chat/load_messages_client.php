<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");
error_reporting(E_ALL);

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;
if (!$username) exit("");

// Find client ID — FULL PostgreSQL compatibility
$stmt = $conn->prepare("
    SELECT id, full_name 
    FROM users
    WHERE email = ?
       OR full_name = ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) exit("");

$client_id = (int)$client["id"];

// Fetch all messages
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = ?
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$messages) exit("");

// Preload media
$media_stmt = $conn->prepare("
    SELECT id, media_type
    FROM chat_media
    WHERE chat_id = ?
");

// Load reactions
$react_stmt = $conn->prepare("
    SELECT emoji, COUNT(*) AS total
    FROM chat_reactions
    WHERE chat_id = ?
    GROUP BY emoji
    ORDER BY total DESC
");

foreach ($messages as $msg) {

    $msgID = $msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $time   = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$msgID'>";

    // Avatar
    echo "<div class='message-avatar'>
            <img src=\"/upload/default-avatar.png\">
          </div>";

    echo "<div class='message-content'>
            <div class='message-bubble'>";

    // Deleted?
    if ($msg["deleted"] == 1) {
        echo "<span class='removed-text'>Message removed</span>";
    } else {

        // -------------------------
        // MEDIA SECTION (GRID STYLE)
        // -------------------------
        $media_stmt->execute([$msgID]);
        $media = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($media) {

            $count = count($media);

            // Messenger-style grid container
            echo "<div class='media-grid media-count-$count'>";

            foreach ($media as $index => $m) {

                $mediaId = $m["id"];
                $file = "get_media_client.php?id=$mediaId";            // full
                $thumb = "get_media_client.php?id=$mediaId&thumb=1";   // thumbnail

                // IMAGE
                if ($m["media_type"] === "image") {

                    // Last image gets +X overlay if >4
                    if ($index === 3 && $count > 4) {
                        $extra = $count - 4;
                        echo "
                        <div class='media-item more-overlay'>
                            <img src='$thumb' data-full='$file'>
                            <div class='more-count'>+$extra</div>
                        </div>";
                        break;
                    }

                    echo "
                    <div class='media-item'>
                        <img src='$thumb' data-full='$file'>
                    </div>";
                }

                // VIDEO
                elseif ($m["media_type"] === "video") {
                    echo "
                    <div class='media-item'>
                        <video muted preload='metadata' data-full='$file'>
                            <source src='$thumb' type='video/mp4'>
                        </video>
                    </div>";
                }

                // OTHER FILES
                else {
                    echo "
                    <div class='media-item'>
                        <a href='$file' download class='file-link'>📎 Download</a>
                    </div>";
                }
            }

            echo "</div>"; // END media-grid
        }

        // TEXT CONTENT
        if (trim($msg["message"]) !== "")
            echo "<div class='msg-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</div>";
    }

    echo "</div>"; // bubble

    // -------------------------
    // TIME + EDITED LABEL
    --------------------------
    echo "<div class='message-time'>$time";
    if ($msg["edited"]) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // -------------------------
    // REACTIONS BAR
    // -------------------------
    $react_stmt->execute([$msgID]);
    $reactions = $react_stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($reactions) {
        echo "<div class='reaction-bar'>";
        foreach ($reactions as $rc) {
            echo "
            <span class='reaction-item'>
                {$rc['emoji']}
                <span class='reaction-count'>{$rc['total']}</span>
            </span>";
        }
        echo "</div>";
    }

    // -------------------------
    // ACTION TOOLBAR (emoji, menu)
    // -------------------------
    echo "<div class='action-toolbar'>";

    echo "<button class='react-btn' data-msg-id='$msgID'>☺︎</button>";

    if ($sender === "sent" && $msg["deleted"] == 0) {
        echo "<button class='more-btn' data-id='$msgID'>⋯</button>";
    }

    echo "</div>"; // toolbar

    echo "</div></div>"; // content + message wrapper
}
?>
