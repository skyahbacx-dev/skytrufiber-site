<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST['client_id'] ?? null;

if (!$client_id) {
    echo "Missing client ID";
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT c.id, c.sender_type, c.message, c.created_at,
               cm.media_path, cm.media_type
        FROM chat c
        LEFT JOIN chat_media cm ON cm.chat_id = c.id
        WHERE c.client_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$client_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row):

        // Determine bubble alignment
        $isMine = ($row["sender_type"] === "csr");
        $class = $isMine ? "sent" : "received";

?>
        <div class="message <?= $class ?>">

            <div class="message-avatar">
                <img src="<?= $isMine ? '../upload/default_avatar.png' : '../upload/default_avatar.png' ?>" alt="avatar">
            </div>

            <div>
                <div class="message-bubble">
                    <?php if (!empty($row["media_path"])): ?>

                        <?php if ($row["media_type"] === "image"): ?>
                            <img src="<?= "../../" . htmlspecialchars($row["media_path"]) ?>"
                                 class="media-thumb"
                                 onclick="openLightbox('<?= "../../" . htmlspecialchars($row["media_path"]) ?>')">
                        <?php else: ?>
                            <a href="<?= "../../" . htmlspecialchars($row["media_path"]) ?>" 
                               class="download-btn" download>
                                📎 Download File
                            </a>
                        <?php endif; ?>

                    <?php endif; ?>

                    <?php if (!empty($row["message"])): ?>
                        <?= nl2br(htmlspecialchars($row["message"])) ?>
                    <?php endif; ?>
                </div>

                <div class="message-time">
                    <?= date("M j g:i A", strtotime($row["created_at"])) ?>
                </div>
            </div>

        </div>

<?php
    endforeach;

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
?>
