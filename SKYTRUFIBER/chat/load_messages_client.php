<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");
error_reporting(E_ALL);

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;
if (!$username) exit("");

// Find client ID — PostgreSQL-compatible (ILIKE or simple equality depending on DB)
// We use case-insensitive comparison with ILIKE for PostgreSQL compatibility.
// If you're using MySQL, ILIKE will behave like LIKE; adjust if needed.
$stmt = $conn->prepare("
    SELECT id, full_name
    FROM users
    WHERE email ILIKE ?
       OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) exit("");

$client_id = (int)$client["id"];

// Fetch all messages for that client
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = ?
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$messages) exit("");

// Prepare media lookup statement
$media_stmt = $conn->prepare("
    SELECT id, media_type
    FROM chat_media
    WHERE chat_id = ?
");

// Prepare reactions lookup statement
$react_stmt = $conn->prepare("
    SELECT emoji, COUNT(*) AS total
    FROM chat_reactions
    WHERE chat_id = ?
    GROUP BY emoji
    ORDER BY total DESC
");

foreach ($messages as $msg) {

    $msgID = (int)$msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $time   = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='{$msgID}'>";

    // Avatar
    echo "<div class='message-avatar'>
            <img src='/upload/default-avatar.png' alt='avatar'>
          </div>";

    echo "<div class='message-content'>
            <div class='message-bubble'>";

    // If deleted
    if ((int)$msg["deleted"] === 1) {
        echo "<span class='removed-text'>Message removed</span>";
    } else {

        // MEDIA SECTION (grid)
        $media_stmt->execute([$msgID]);
        $media = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($media) {

            $count = count($media);
            // Normalize count to class-friendly value (1..4, others treated as 4+)
            $gridCountClass = $count;
            if ($gridCountClass > 4) $gridCountClass = 4;

            echo "<div class='media-grid media-count-{$gridCountClass}'>";

            foreach ($media as $index => $m) {

                $mediaId = (int)$m["id"];
                $full = "get_media_client.php?id={$mediaId}";
                $thumb = "get_media_client.php?id={$mediaId}&thumb=1";

                if ($m["media_type"] === "image") {

                    // If more than 4 images and this is the 4th slot, show +X more overlay
                    if ($index === 3 && $count > 4) {
                        $extra = $count - 4;
                        echo "<div class='media-item more-overlay' data-more='+{$extra}'>";
                        echo "<img src='{$thumb}' data-full='{$full}' alt='img'>";
                        echo "<div class='more-count'>+{$extra}</div>";
                        echo "</div>";
                        break;
                    }

                    echo "<div class='media-item'>";
                    echo "<img src='{$thumb}' data-full='{$full}' alt='img'>";
                    echo "</div>";
                }
                elseif ($m["media_type"] === "video") {
                    echo "<div class='media-item'>";
                    echo "<video muted preload='metadata' data-full='{$full}'>";
                    echo "<source src='{$thumb}' type='video/mp4'>";
                    echo "</video>";
                    echo "</div>";
                }
                else {
                    // Generic file link
                    echo "<div class='media-item'>";
                    echo "<a href='{$full}' download class='file-link'>📎 Download</a>";
                    echo "</div>";
                }
            }

            echo "</div>"; // end media-grid
        }

        // TEXT
        if (trim($msg["message"]) !== "") {
            echo "<div class='msg-text'>" . nl2br(htmlspecialchars($msg["message"])) . "</div>";
        }
    }

    echo "</div>"; // end message-bubble

    // Timestamp + edited label
    echo "<div class='message-time'>{$time}";
    if (!empty($msg["edited"])) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // Reactions (grouped)
    $react_stmt->execute([$msgID]);
    $reactions = $react_stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($reactions) {
        echo "<div class='reaction-bar'>";
        foreach ($reactions as $rc) {
            $emoji = htmlspecialchars($rc['emoji']);
            $total = (int)$rc['total'];
            echo "<span class='reaction-item'><span class='reaction-emoji'>{$emoji}</span><span class='reaction-count'>{$total}</span></span>";
        }
        echo "</div>";
    }

    // Action toolbar
    echo "<div class='action-toolbar'>";
    echo "<button class='react-btn' data-msg-id='{$msgID}'>☺︎</button>";
    if ($sender === "sent" && (int)$msg["deleted"] === 0) {
        echo "<button class='more-btn' data-id='{$msgID}'>⋯</button>";
    }
    echo "</div>";

    echo "</div></div>"; // end message-content + message wrapper
}
