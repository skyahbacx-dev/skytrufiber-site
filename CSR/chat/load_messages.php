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

        // Avatar
        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
              </div>";

        echo "<div class='message-content'>";

        /* ==========================
           ACTION TOOLBAR (⋮ button)
        =========================== */
        echo "
            <div class='action-toolbar'>
                <button class='more-btn' data-id='$msgID'>
                    <i class='fa-solid fa-ellipsis-vertical'></i>
                </button>
            </div>
        ";

        echo "<div class='message-bubble'>";

        /* =====================================================
           LOAD MEDIA
        ====================================================== */
        $mediaStmt = $conn->prepare("
            SELECT id, media_type
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

        $count = count($mediaList);

        /* Determine grid layout class */
        if ($count == 1)            $gridClass = "media-1";
        elseif ($count == 2)        $gridClass = "media-2";
        elseif ($count == 3)        $gridClass = "media-3";
        elseif ($count == 4)        $gridClass = "media-4";
        elseif ($count >= 5)        $gridClass = "media-5";
        else                        $gridClass = "";

        /* ==========================
           RENDER MEDIA GRID
        =========================== */
        if ($count > 0) {

            echo "<div class='media-grid $gridClass'>";

            // For 5+ media, show only 4 and overlay "+X more"
            $limit = ($count >= 5) ? 4 : $count;

            for ($i = 0; $i < $limit; $i++) {

                $m = $mediaList[$i];
                $mediaID = (int)$m["id"];
                $src = "../chat/get_media.php?id=$mediaID";

                // Overlay needed?
                $isLast = ($count >= 5 && $i == 3);
                $extraDiv = $isLast ? "media-more-overlay' data-more='+".($count-4)."'" : "'";

                echo "<div class=$extraDiv>";

                if ($m["media_type"] === "image") {
                    echo "<img src='$src' data-full='$src' class='fullview-item'>";
                }
                elseif ($m["media_type"] === "video") {
                    echo "<video data-full='$src' class='fullview-item'>
                            <source src='$src' type='video/mp4'>
                          </video>";
                }
                else {
                    echo "<a href='$src' download class='download-btn'>📎 File</a>";
                }

                echo "</div>";
            } // for each media

            echo "</div>"; // media-grid
        }

        /* ==========================
           TEXT MESSAGE
        =========================== */
        if (!empty($msg["message"])) {
            $safeText = nl2br(htmlspecialchars($msg["message"]));
            echo "<div class='msg-text'>$safeText</div>";
        }

        echo "</div>"; // message-bubble

        /* Edited label */
        if (!empty($msg["edited"])) {
            echo "<div class='edited-label'>(edited)</div>";
        }

        echo "<div class='message-time'>$timestamp</div>";

        echo "</div>"; // content
        echo "</div>"; // wrapper
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
