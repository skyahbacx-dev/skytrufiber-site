<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

try {

    /* ============================================================
       1) Fetch all messages for this client
    ============================================================ */
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

    $msgIDs = array_column($messages, "id");

    /* ============================================================
       2) Batch-load ALL media in ONE query
    ============================================================ */
    $mediaMap = [];
    if (!empty($msgIDs)) {
        $in = implode(",", array_fill(0, count($msgIDs), "?"));
        $query = $conn->prepare("SELECT id, chat_id, media_type FROM chat_media WHERE chat_id IN ($in)");
        $query->execute($msgIDs);
        $allMedia = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allMedia as $m) {
            $mediaMap[$m["chat_id"]][] = $m;
        }
    }

    /* ============================================================
       3) Rendering messages
    ============================================================ */
    $globalIndex = 0;

    foreach ($messages as $msg) {

        $msgID = (int)$msg["id"];
        $sender = ($msg["sender_type"] === "csr") ? "sent" : "received";
        $timestamp = date("M j g:i A", strtotime($msg["created_at"]));
        $mediaList = $mediaMap[$msgID] ?? [];

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        // Avatar
        echo "
            <div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
            </div>
        ";

        echo "<div class='message-content'>";

        // More button
        echo "
            <button class='more-btn' data-id='$msgID'>
                <i class='fa-solid fa-ellipsis-vertical'></i>
            </button>
        ";

        /* ============================================================
           MESSAGE BUBBLE
        ============================================================ */
        echo "<div class='message-bubble'>";

        /* ------------------------ MEDIA GRID ------------------------ */
        $count = count($mediaList);

        if ($count > 0) {

            // Determine grid layout
            if ($count === 1) $grid = "media-1";
            elseif ($count === 2) $grid = "media-2";
            elseif ($count === 3) $grid = "media-3";
            elseif ($count === 4) $grid = "media-4";
            else $grid = "media-5";

            echo "<div class='media-grid $grid'>";

            // For 5+ show 4 only, then overlay
            $visibleCount = ($count >= 5 ? 4 : $count);

            for ($i = 0; $i < $visibleCount; $i++) {

                $media = $mediaList[$i];
                $mediaID = (int)$media["id"];
                $src = "../chat/get_media.php?id=$mediaID";

                $indexAttr = "data-media-index='{$globalIndex}'";
                $globalIndex++;

                $openTag = "<div class='media-item'>";

                // Extra overlay for 5+ items
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
                } else {
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

            echo "</div>"; // end grid
        }

        /* ------------------------ MESSAGE TEXT ------------------------ */
        if (!empty($msg["message"])) {
            $safe = nl2br(htmlspecialchars($msg["message"]));
            echo "<div class='msg-text'>$safe</div>";
        }

        echo "</div>"; // close .message-bubble

        /* ------------------------ Edited Label ------------------------ */
        if (!empty($msg["edited"])) {
            echo "<div class='edited-label'>(edited)</div>";
        }

        /* ------------------------ Timestamp ------------------------ */
        echo "<div class='message-time'>$timestamp</div>";

        echo "</div>"; // close content
        echo "</div>"; // close message wrapper
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
