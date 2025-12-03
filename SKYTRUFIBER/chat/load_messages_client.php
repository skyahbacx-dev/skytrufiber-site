<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");
error_reporting(E_ALL);

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
if (!$username) exit("");

// Get user (email OR fullname)
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

// Fetch chat messages
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = ?
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$messages) exit("");

// Prepare media lookup
$media_stmt = $conn->prepare("
    SELECT id, media_type
    FROM chat_media
    WHERE chat_id = ?
");

// Prepare reactions lookup
$reaction_stmt = $conn->prepare("
    SELECT emoji, COUNT(*) AS total
    FROM chat_reactions
    WHERE chat_id = ?
    GROUP BY emoji
    ORDER BY total DESC
");

foreach ($messages as $msg) {

    $msgID  = $msg["id"];
    $sender = ($msg["sender_type"] === "csr") ? "received" : "sent";

    $time = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$msgID'>";

    // Avatar
    echo "<div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
          </div>";

    echo "<div class='message-content'>
            <div class='message-bubble'>";

    // If removed
    if ($msg["deleted"] == 1) {
        echo "<span class='removed-text'>Message removed</span>";
    } else {

        // ---------- MEDIA ----------
        $media_stmt->execute([$msgID]);
        $mediaFiles = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($mediaFiles) {
            if (count($mediaFiles) > 1)
                echo "<div class='carousel-container'>";

            foreach ($mediaFiles as $m) {
                $fileID = $m["id"];
                $full   = "get_media_client.php?id=$fileID";
                $thumb  = "get_media_client.php?id=$fileID&thumb=1";

                if ($m["media_type"] === "image") {
                    echo "<img class='media-thumb' src='$thumb' data-full='$full'>";
                } 
                elseif ($m["media_type"] === "video") {
                    echo "<video class='media-video' data-full='$full' muted preload='metadata'>
                            <source src='$thumb' type='video/mp4'>
                          </video>";
                } 
                else {
                    echo "<a href='$full' download class='file-link'>📎 $fileID</a>";
                }
            }

            if (count($mediaFiles) > 1)
                echo "</div>";
        }

        // ---------- TEXT ----------
        if (trim($msg["message"]) !== "")
            echo nl2br(htmlspecialchars($msg["message"]));
    }

    echo "</div>"; // bubble

    // Timestamp + edited label
    echo "<div class='message-time'>$time";
    if ($msg["edited"]) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // ---------- REACTIONS (Messenger Style) ----------
    $reaction_stmt->execute([$msgID]);
    $reacts = $reaction_stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($reacts) {
        echo "<div class='reaction-bar'>";
        foreach ($reacts as $r) {
            $emoji = htmlspecialchars($r["emoji"]);
            $total = (int)$r["total"];

            echo "
                <span class='reaction-item'>
                    <span class='reaction-emoji'>$emoji</span>
                    <span class='reaction-count'>$total</span>
                </span>
            ";
        }
        echo "</div>";
    }

    // -------- ACTIONS (react + more) --------
    echo "<div class='action-toolbar'>
            <button class='react-btn' data-msg-id='$msgID'>☺︎</button>";

    if ($sender === "sent" && $msg["deleted"] == 0) {
        echo "<button class='more-btn' data-id='$msgID'>⋯</button>";
    }

    echo "</div>";

    echo "</div></div>"; // END message wrapper
}
?>
