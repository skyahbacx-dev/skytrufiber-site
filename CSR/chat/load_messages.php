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

        /* Avatar (optional) */
        echo "<div class='message-avatar'>
                <img src='/upload/default-avatar.png'>
              </div>";

        echo "<div class='message-content'>";

        /* ==========================
           ACTION TOOLBAR (⋮)
        =========================== */
        echo "
            <div class='action-toolbar'>
                <button class='more-btn' data-id='$msgID'>
                    <i class='fa-solid fa-ellipsis-vertical'></i>
                </button>
            </div>
        ";

        echo "<div class='message-bubble'>";

        /* ----------------------------------------------------
           LOAD MEDIA (multiple or single)
        ----------------------------------------------------- */
        $mediaStmt = $conn->prepare("
            SELECT id, media_type 
            FROM chat_media
            WHERE chat_id = ?
        ");
        $mediaStmt->execute([$msgID]);
        $mediaList = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

/* ==============================
   MULTIPLE MEDIA (GRID)
============================== */
if ($mediaList && count($mediaList) > 1) {

    $count = count($mediaList);

    $cols = "cols-1";
    if ($count == 2) $cols = "cols-2";
    if ($count == 3) $cols = "cols-3";
    if ($count >= 4) $cols = "cols-4";

    echo "<div class='media-grid $cols'>";

    foreach ($mediaList as $m) {

        $mediaID = (int)$m["id"];
        $src = "../chat/get_media.php?id=$mediaID";

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
    }

    echo "</div>"; // media-grid
}


/* ==============================
   SINGLE MEDIA
============================== */
elseif ($mediaList && count($mediaList) === 1) {

    $cols = "cols-1";
    echo "<div class='media-grid $cols'>";

    $media = $mediaList[0];
    $mediaID = (int)$media["id"];
    $src = "../chat/get_media.php?id=$mediaID";

    if ($media["media_type"] === "image") {
        echo "<img src='$src' data-full='$src' class='fullview-item'>";
    }
    elseif ($media["media_type"] === "video") {
        echo "<video class='fullview-item' controls>
                <source src='$src' type='video/mp4'>
              </video>";
    }
    else {
        echo "<a href='$src' download class='download-btn large'>📎 Download File</a>";
    }

    echo "</div>";
}


        /* ==========================
           TEXT MESSAGE
        ========================== */
        if (!empty($msg["message"])) {
            $safeText = nl2br(htmlspecialchars($msg["message"]));
            echo "<div class='msg-text'>$safeText</div>";
        }

        echo "</div>"; // message bubble

        /* Edited Tag */
        if (!empty($msg["edited"])) {
            echo "<div class='edited-label'>(edited)</div>";
        }

        echo "<div class='message-time'>$timestamp</div>";

        echo "</div>"; // message-content
        echo "</div>"; // message wrapper
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
