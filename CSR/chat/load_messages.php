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

        /* AVATAR */
        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
              </div>";

        echo "<div class='message-content'>";

        /* ==========================
           ACTION TOOLBAR
        =========================== */
        echo "
            <div class='action-toolbar'>
                <button class='more-btn' data-id='$msgID'>
                    <i class='fa-solid fa-ellipsis-vertical'></i>
                </button>
            </div>
        ";

        echo "<div class='message-bubble'>";

        /* ==========================
           LOAD MEDIA LIST
        =========================== */
        $mediaStmt = $conn->prepare("
            SELECT id, media_type
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        $count = count($mediaList);

        /* Determine grid class: 1–10 items */
        if ($count <= 1)      $gridClass = "media-1";
        elseif ($count == 2) $gridClass = "media-2";
        elseif ($count == 3) $gridClass = "media-3";
        elseif ($count == 4) $gridClass = "media-4";
        elseif ($count == 5) $gridClass = "media-5";
        elseif ($count == 6) $gridClass = "media-6";
        elseif ($count == 7) $gridClass = "media-7";
        elseif ($count == 8) $gridClass = "media-8";
        elseif ($count == 9) $gridClass = "media-9";
        else                 $gridClass = "media-10"; // 10+ items → max grid

        /* ==========================
           RENDER MEDIA GRID
        =========================== */
        if ($count > 0) {

            echo "<div class='media-grid $gridClass'>";

            // 5+ = show first 4 + overlay
            $visible = ($count >= 5) ? 4 : $count;

            for ($i = 0; $i < $visible; $i++) {

                $m = $mediaList[$i];
                $mediaID = (int)$m["id"];
                
                // Use thumbnail mode for faster grid load
                $fullSrc = "../chat/get_media.php?id=$mediaID";
                $thumbSrc = "../chat/get_media.php?id=$mediaID&thumb=1";

                $overlay = "";
                $openDiv = "<div class='media-item'>";

                if ($count >= 5 && $i == 3) {
                    $extra = $count - 4;
                    $openDiv = "<div class='media-item media-more-overlay' data-more='+$extra'>";
                }

                echo $openDiv;

                /* IMAGE */
                if ($m["media_type"] === "image") {
                    echo "
                        <img src='$thumbSrc'
                             data-full='$fullSrc'
                             class='fullview-item media-thumb'>
                    ";
                }

                /* VIDEO – show snapshot thumbnail */
                elseif ($m["media_type"] === "video") {

                    // Use poster frame via same thumb endpoint (fast)
                    echo "
                        <video class='media-thumb fullview-item' 
                               data-full='$fullSrc'
                               muted>
                            <source src='$fullSrc' type='video/mp4'>
                        </video>
                    ";
                }

                /* FILE */
                else {
                    echo "<a href='$fullSrc' download class='download-btn'>📎 File</a>";
                }

                echo "</div>"; // end media-item
            }

            echo "</div>"; // end media-grid
        }

        /* ==========================
           TEXT MESSAGE
        =========================== */
        if (!empty($msg["message"])) {
            $safe = nl2br(htmlspecialchars($msg["message"]));
            echo "<div class='msg-text'>$safe</div>";
        }

        echo "</div>"; // message-bubble

        /* Edited tag */
        if (!empty($msg["edited"])) {
            echo "<div class='edited-label'>(edited)</div>";
        }

        echo "<div class='message-time'>$timestamp</div>";

        echo "</div>"; // message-content
        echo "</div>"; // message
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
