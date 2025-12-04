<?php
require "db.php";

$username = $_POST["username"] ?? "";

if (!$username) {
    echo "<p style='padding:20px;text-align:center;'>Invalid user.</p>";
    exit;
}

/* ---------------------------------------------------------
   Fetch conversation
--------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT c.id, c.sender, c.message, c.media_path, c.media_type,
           c.created_at,
           (
               SELECT JSON_AGG(
                   JSON_BUILD_OBJECT('emoji', r.emoji, 'count', COUNT(*))
               )
               FROM chat_reactions r
               WHERE r.chat_id = c.id
               GROUP BY r.emoji
           ) AS reactions
    FROM chat_messages c
    WHERE c.username = ?
    ORDER BY c.id ASC
");
$stmt->execute([$username]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------------------------------------------------
   Render Messages
--------------------------------------------------------- */

foreach ($rows as $msg):

    $isSent = $msg["sender"] === $username ? "sent" : "received";
    $msgID  = $msg["id"];
?>

<div class="message <?= $isSent ?>" data-msg-id="<?= $msgID ?>">

    <!-- AVATAR -->
    <div class="message-avatar">
        <img src="/upload/default-avatar.png">
    </div>

    <div class="message-content">

        <!-- TEXT BUBBLE -->
        <?php if (!empty($msg["message"])): ?>
            <div class="message-bubble"><?= nl2br(htmlspecialchars($msg["message"])) ?></div>
        <?php endif; ?>

        <!-- MEDIA SECTION -->
        <?php if (!empty($msg["media_path"])): ?>

            <?php
            $files = explode(",", $msg["media_path"]);
            $types = explode(",", $msg["media_type"]);
            ?>

            <div class="media-grid">
                <?php foreach ($files as $i => $file): ?>
                    <?php if ($types[$i] === "image"): ?>
                        <img src="<?= $file ?>" data-full="<?= $file ?>" class="media-thumb">
                    <?php else: ?>
                        <video src="<?= $file ?>" data-full="<?= $file ?>" class="media-video"></video>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

        <!-- REACTION BAR -->
        <?php if ($msg["reactions"]): ?>
            <div class="reaction-bar">
                <?php foreach (json_decode($msg["reactions"], true) as $r): ?>
                    <div class="reaction-item">
                        <span><?= $r["emoji"] ?></span>
                        <span><?= $r["count"] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- TIMESTAMP -->
        <div class="timestamp">
            <?= date("g:i A", strtotime($msg["created_at"])) ?>
        </div>

        <!-- ACTION TOOLBAR (Hover) -->
        <div class="action-toolbar">
            <button class="react-btn" data-msg-id="<?= $msgID ?>">😊</button>
            <button class="more-btn" data-id="<?= $msgID ?>">⋯</button>
        </div>

    </div>
</div>

<?php endforeach; ?>
