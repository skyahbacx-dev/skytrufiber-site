<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

try {

    // Fetch messages
    $stmt = $conn->prepare("
        SELECT id, sender_type, message, created_at, edited
        FROM chat
        WHERE client_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$client_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$messages) {
        echo "<p style='text-align:center;color:#777;padding:10px;'>No messages yet.</p>";
        exit;
    }

    // GLOBAL MEDIA INDEX FOR LIGHTBOX
    $globalIndex = 0;

    foreach ($messages as $msg) {

        $msgID = (int)$msg["id"];
        $sender = ($msg["sender_type"] === "csr") ? "sent" : "received";
        $timestamp = date("M j g:i A", strtotime($msg["created_at"]));

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        // Avatar
        echo "
            <div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
            </div>
        ";

        echo "<div class='message-content'>";

        // MORE BUTTON
        echo "
            <button class='more-btn' data-id='$msgID'>
                <i class='fa-solid fa-ellipsis-vertical'></i>
            </button>
        ";

        // MESSAGE BUBBLE START
        echo "<div class='message-bubble'>";

        /* -----------------------------------------------------------
           LOAD MEDIA ATTACHMENTS
        ------------------------------------------------------------ */
        $m = $conn->prepare("SELECT id, media_type FROM chat_media WHERE chat_id = ?");
        $m->execute([$msgID]);
        $mediaList = $m->fetchAll(PDO::FETCH_ASSOC);

        $count = count($mediaList);

        if ($count > 0) {

            // Determine grid class
            if ($count === 1) $grid = "media-1";
            elseif ($count === 2) $grid = "media-2";
            elseif ($count === 3) $grid = "media-3";
            elseif ($count === 4) $grid = "media-4";
            else $grid = "media-5"; // 5 or more

            echo "<div class='media-grid $grid'>";

            $visibleCount = ($count >= 5 ? 4 : $count);

            for ($i = 0; $i < $visibleCount; $i++) {

                $media = $mediaList[$i];
                $mediaID = (int)$media["id"];
                $src = "../chat/get_media.php?id=$mediaID";

                $indexAttr = "data-media-index='{$globalIndex}'";
                $globalIndex++;

                // Overlay for extra items
                $overlay = "";
                $openTag = "<div class='media-item'>";

                if ($count >= 5 && $i === 3) {
                    $extra = $count - 4;
                    $openTag = "<div class='media-item media-more-overlay' data-more='+$extra'>";
                }

                echo $openTag;

                if ($media["media_type"] === "image") {
                    echo "
                        <img src='$src'
                             class='fullview-item'
                             data-full='$src'
                             $indexAttr>
                    ";
                }
                elseif ($media["media_type"] === "video") {
                    echo "
                        <video class='fullview-item'
                               data-full='$src'
                               $indexAttr muted>
                            <source src='$src' type='video/mp4'>
                        </video>
                    ";
                }

                echo "</div>";
            }

            echo "</div>"; // end media-grid
        }

        /* -----------------------------------------------------------
           MESSAGE TEXT
        ------------------------------------------------------------ */
        if (!empty($msg["message"])) {
            $safe = nl2br(htmlspecialchars($msg["message"]));
            echo "<div class='msg-text'>$safe</div>";
        }

        echo "</div>"; // close message-bubble

        // Edited label
        if (!empty($msg["edited"])) {
            echo "<div class='edited-label'>(edited)</div>";
        }

        // Timestamp
        echo "<div class='message-time'>$timestamp</div>";

        echo "</div>"; // close message-content
        echo "</div>"; // close .message
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
