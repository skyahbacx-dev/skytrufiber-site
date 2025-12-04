<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
if ($username === "") exit("");

// -------------------------------------------------
// FIND CLIENT (email OR full_name)
// -------------------------------------------------
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

// -------------------------------------------------
// FETCH MESSAGES
// -------------------------------------------------
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = ?
    ORDER BY id ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// -------------------------------------------------
// PREPARE MEDIA FETCHER
// -------------------------------------------------
$mstmt = $conn->prepare("
    SELECT id, media_type
    FROM chat_media
    WHERE chat_id = ?
");

// -------------------------------------------------
// OUTPUT MESSAGES
// -------------------------------------------------
foreach ($messages as $msg) {

    $id        = (int)$msg["id"];
    $sender    = $msg["sender_type"] === "csr" ? "received" : "sent";
    $time      = date("g:i A", strtotime($msg["created_at"]));
    $deleted   = (int)$msg["deleted"];
    $isEdited  = (int)$msg["edited"];

    // SENT MESSAGES HAVE NO AVATAR
    $noAvatarClass = ($sender === "sent") ? "no-avatar" : "";

    echo "<div class='message $sender $noAvatarClass' data-msg-id='$id'>";

    // Avatar only for received
    if ($sender === "received") {
        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
              </div>";
    }

    echo "<div class='message-content'>";
    echo "<div class='message-bubble'>";

    // If deleted
    if ($deleted) {
        echo "<span class='removed-text'>Message removed</span>";
    } else {

        // MEDIA LOADING
        $mstmt->execute([$id]);
        $media = $mstmt->fetchAll(PDO::FETCH_ASSOC);

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
                    echo "<a href='$file' download class='file-link'>📎 File</a>";
                }
            }
            echo "</div>";
        }

        // TEXT MESSAGE
        if (trim($msg["message"]) !== "") {
            echo nl2br(htmlspecialchars($msg["message"]));
        }
    }

    echo "</div>"; // bubble

    // TIME + edited
    echo "<div class='message-time'>$time";
    if ($isEdited) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // -------------------------------------------------
    // REACTION BAR
    // -------------------------------------------------
    $r = $conn->prepare("
        SELECT emoji, COUNT(*) AS total
        FROM chat_reactions
        WHERE chat_id = ?
        GROUP BY emoji
        ORDER BY total DESC
    ");
    $r->execute([$id]);
    $reactList = $r->fetchAll(PDO::FETCH_ASSOC);

    if ($reactList) {
        echo "<div class='reaction-bar'>";
        foreach ($reactList as $rc) {
            echo "<span class='reaction-item'>
                    {$rc['emoji']} <span class='reaction-count'>{$rc['total']}</span>
                  </span>";
        }
        echo "</div>";
    }

    // -------------------------------------------------
    // ACTION TOOLBAR
    // -------------------------------------------------
    echo "<div class='action-toolbar'>
            <button class='react-btn' data-msg-id='$id'>☺︎</button>";

    if ($sender === "sent" && !$deleted) {
        echo "<button class='more-btn' data-id='$id'>⋯</button>";
    }

    echo "</div>"; // toolbar

    echo "</div>"; // content
    echo "</div>"; // message wrapper
}
