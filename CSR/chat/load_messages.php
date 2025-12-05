<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id) exit;

try {

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

    foreach ($messages as $msg) {

        $msgID     = (int)$msg["id"];
        $sender    = ($msg["sender_type"] === "csr") ? "sent" : "received";
        $timestamp = date("M j g:i A", strtotime($msg["created_at"]));

        echo "<div class='message $sender' data-msg-id='$msgID'>";

        /* Avatar */
        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
              </div>";

        echo "<div class='message-content'>";

        /* Action Toolbar */
        echo "
            <div class='action-toolbar'>
                <button class='more-btn' data-id='$msgID'>
                    <i class='fa-solid fa-ellipsis-vertical'></i>
                </button>
            </div>
        ";

        echo "<div class='message-bubble'>";

        /* Load media list */
        $mediaStmt = $conn->prepare("
            SELECT id, media_type
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        $count = count($mediaList);

        /* Match existing CSS layout exactly */
        if ($count == 1)        $gridClass = "media-1";
        elseif ($count == 2)    $gridClass = "media-2";
        elseif ($count == 3)    $gridClass = "media-3";
        elseif ($count == 4)    $gridClass = "media-4";
        elseif ($count >= 5)    $gridClass = "media-5"; // 5 or more → overlay layout
        else                    $gridClass = "";

        /* Render media grid */
        if ($count > 0) {

            echo "<div class='media-grid $gridClass'>";

            $visible = ($count >= 5) ? 4 : $count;

            for ($i = 0; $i < $visible; $i++) {

                $m = $mediaList[$i];
                $mediaID = (int)$m["id"];

                $fullSrc  = "../chat/get_media.php?id=$mediaID";
                $thumbSrc = "../chat/get_media.php?id=$mediaID"; // same endpoint (your system does NOT support thumbnails)

                $isOverlay = ($count >= 5 && $i == 3);
                $extraMore = $count - 4;

                echo $isOverlay
                    ? "<div class='media-item media-more-overlay' data-more='+$extraMore'>"
                    : "<div class='media-item'>";

                /* IMAGE */
                if ($m["media_type"] === "image") {
                    echo "
                        <img src='$thumbSrc'
                             data-full='$fullSrc'
                             class='fullview-item media-thumb'>
                    ";
                }

                /* VIDEO */
                elseif ($m["media_type"] === "video") {
                    echo "
                        <video class='media-thumb fullview-item' data-full='$fullSrc' muted>
                            <source src='$fullSrc' type='video/mp4'>
                        </video>
                    ";
                }

                /* FILE */
                else {
                    echo "<a href='$fullSrc' download class='download-btn'>📎 File</a>";
                }

                echo "</div>";
            }

            echo "</div>";
        }

        /* Text message */
        if (!empty($msg["message"])) {
            $safe = nl2br(htmlspecialchars($msg["message"]));
            echo "<div class='msg-text'>$safe</div>";
        }

        echo "</div>"; // bubble

        if (!empty($msg["edited"]))
            echo "<div class='edited-label'>(edited)</div>";

        echo "<div class='message-time'>$timestamp</div>";

        echo "</div>"; // content
        echo "</div>"; // message wrapper
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
