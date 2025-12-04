<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = $_POST["username"] ?? "";
if (!$username) exit("");

// -----------------------------
// Find client
// -----------------------------
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

// -----------------------------
// Fetch chat messages
// -----------------------------
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = ?
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Media preload statement
$mstmt = $conn->prepare("
    SELECT id, media_type
    FROM chat_media
    WHERE chat_id = ?
");

// Start output
foreach ($messages as $msg) {

    $id     = $msg["id"];
    $sender = $msg["sender_type"] === "csr" ? "received" : "sent";
    $time   = date("g:i A", strtotime($msg["created_at"]));

    echo "<div class='message $sender' data-msg-id='$id'>";

    // Avatar
    echo "<div class='message-avatar'>
            <img src='/upload/default-avatar.png'>
          </div>";

    echo "<div class='message-content'>";

    echo "<div class='message-bubble'>";

    if ($msg["deleted"]) {
        echo "<span class='removed-text'>Message removed</span>";
    } else {

        // Fetch media for this message
        $mstmt->execute([$id]);
        $media = $mstmt->fetchAll(PDO::FETCH_ASSOC);

        // -----------------------------
        // MEDIA GRID
        // -----------------------------
        if ($media) {
            echo "<div class='media-grid'>";

            foreach ($media as $m) {

                $file  = "get_media_client.php?id={$m["id"]}";
                $thumb = "get_media_client.php?id={$m["id"]}&thumb=1";

                if ($m["media_type"] === "image") {
                    echo "<img src='$thumb' data-full='$file'>";
                }
                elseif ($m["media_type"] === "video") {
                    echo "<video muted preload='metadata' data-full='$file'>
                            <source src='$thumb' type='video/mp4'>
                          </video>";
                }
                else {
                    echo "<a href='$file' download class='file-link'>📎 $file</a>";
                }
            }

            echo "</div>";
        }

        // Text message
        if (trim($msg["message"]) !== "")
            echo nl2br(htmlspecialchars($msg["message"]));
    }

    echo "</div>"; // bubble

    // Time + edited label
    echo "<div class='message-time'>$time";
    if ($msg["edited"]) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // -----------------------------
    // REACTIONS BAR
    // -----------------------------
    $r = $conn->prepare("
        SELECT emoji, COUNT(*) AS total
        FROM chat_reactions
        WHERE chat_id = ?
        GROUP BY emoji
        ORDER BY total DESC
    ");
    $r->execute([$id]);
    $react = $r->fetchAll(PDO::FETCH_ASSOC);

    if ($react) {
        echo "<div class='reaction-bar'>";
        foreach ($react as $rc) {
            echo "<span class='reaction-item'>{$rc['emoji']} <span class='reaction-count'>{$rc['total']}</span></span>";
        }
        echo "</div>";
    }

    // -----------------------------
    // ACTION TOOLBAR (😊 ...)
    // -----------------------------
echo "<div class='action-toolbar'>
        <button class='react-btn' data-msg-id='$id'>☺︎</button>";

if ($sender === "sent" && !$msg["deleted"]) {
    echo "<button class='more-btn' data-id='$id'>⋯</button>";
}

echo "</div>";


    echo "</div></div>"; // close content + message
}
