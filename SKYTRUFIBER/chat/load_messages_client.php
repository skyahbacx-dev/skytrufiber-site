<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;
if (!$username) exit("No username");

// Lookup customer by email or full name
$stmt = $conn->prepare("
    SELECT id, full_name
    FROM users
    WHERE email = ? OR full_name = ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$clientRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clientRow) exit("User not found");

$client_id = (int)$clientRow["id"];

// Fetch chat messages including delete + edited flags
$stmt = $conn->prepare("
    SELECT id, sender_type, message, created_at, deleted, edited
    FROM chat
    WHERE client_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$messages) exit;

// avatars
$csrAvatar  = "/upload/default-avatar.png";
$userAvatar = "/upload/default-avatar.png";

foreach ($messages as $msg) {

    $msgID     = (int)$msg["id"];
    $sender    = ($msg["sender_type"] === "csr") ? "received" : "sent";
    $avatar    = ($sender === "received") ? $csrAvatar : $userAvatar;
    $timestamp = date("g:i A", strtotime($msg["created_at"]));
    $isEdited  = ($msg["edited"] == 1);

    echo "<div class='message $sender fadeup' data-msg-id='$msgID'>";

    echo "<div class='message-avatar'>
            <img src='$avatar' alt='avatar'>
          </div>";

    echo "<div class='message-content'>";

    echo "<div class='message-bubble'>";

    // Deleted placeholder
    if ($msg['deleted'] == 1) {

        echo "<span class='removed-text'>Message removed</span>";

    } else {

        // Load media attachments
        $mQuery = $conn->prepare("
            SELECT id, media_type
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mQuery->execute([$msgID]);
        $mediaList = $mQuery->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($mediaList)) {

            if (count($mediaList) > 1) echo "<div class='carousel-container'>";

            foreach ($mediaList as $m) {
                $mediaID   = (int)$m['id'];
                $filePath  = "get_media_client.php?id=$mediaID";
                $thumbPath = "get_media_client.php?id=$mediaID&thumb=1";

                if ($m['media_type'] === "image") {
                    echo "<img src='$thumbPath' data-full='$filePath' class='media-thumb'>";
                }
                elseif ($m['media_type'] === "video") {
                    echo "<video muted preload='metadata' data-full='$filePath' class='media-video'>
                            <source src='$thumbPath' type='video/mp4'>
                          </video>";
                }
                else {
                    echo "<a href='$filePath' download class='file-link'>📎 Download File</a>";
                }
            }

            if (count($mediaList) > 1) echo "</div>";
        }

        // Actual text
        if (!empty($msg['message'])) {
            echo nl2br(htmlspecialchars($msg['message']));
        }
    }

    echo "</div>"; // end bubble

    // Reactions bar
    $r = $conn->prepare("
        SELECT emoji, COUNT(*) AS total
        FROM chat_reactions
        WHERE chat_id = ?
        GROUP BY emoji
        ORDER BY total DESC
    ");
    $r->execute([$msgID]);
    $reactions = $r->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($reactions)) {
        echo "<div class='reaction-bar'>";
        foreach ($reactions as $rc) {
            echo "<span class='reaction-item'>{$rc['emoji']} <span class='reaction-count'>{$rc['total']}</span></span>";
        }
        echo "</div>";
    }

    // Timestamp + edited label
    echo "<div class='message-time'>$timestamp";
    if ($isEdited) echo " <span class='edited-label'>(edited)</span>";
    echo "</div>";

    // Actions toolbar (Reaction + More)
    echo "<div class='action-toolbar'>
            <button class='react-btn' data-msg-id='$msgID'>😊</button>";

    // show menu button only for client's messages that are not deleted
    if ($sender === "sent" && $msg['deleted'] == 0) {
        echo "<button class='more-btn' data-id='$msgID'>⋯</button>";
    }

    echo "</div>"; // end action-toolbar

    echo "</div></div>"; // content + message container
}
?>
