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
               cm.media_path, cm.media_type,
               u.full_name, u.avatar_path
        FROM chat c
        LEFT JOIN chat_media cm ON cm.chat_id = c.id
        LEFT JOIN users u ON u.id = c.client_id
        WHERE c.client_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$client_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lastSender = null;

    foreach ($rows as $row) {

        $sender = ($row["sender_type"] === "csr") ? "csr" : "client";
        $avatar = $row["avatar_path"] ? "/" . $row["avatar_path"] : null;

        $showAvatar = ($sender !== $lastSender);
        $lastSender = $sender;

        echo "<div class='chat-row {$sender}'>";

        if ($showAvatar) {
            if ($sender === "client") {
                if ($avatar) {
                    echo "<img class='avatar client-avatar' src='{$avatar}'>";
                } else {
                    echo "<div class='avatar client-avatar letter-avatar'>" . strtoupper($row["full_name"][0]) . "</div>";
                }
            } else {
                echo "<div class='avatar csr-avatar'>CSR</div>";
            }
        }

        echo "<div class='msg {$sender}'>";

        if (!empty($row["media_path"])) {
            if ($row["media_type"] === "image") {
                echo "<img src='/" . $row["media_path"] . "' class='media-thumb' onclick='openLightbox(this.src)'>";
            } else {
                echo "<a href='/" . $row["media_path"] . "' download class='download-btn'>📎 Download File</a>";
            }
        }

        if (!empty($row["message"])) {
            echo nl2br(htmlspecialchars($row["message"]));
        }

        echo "</div>";
        echo "</div>";

        echo "<div class='timestamp {$sender}'>" . date("M j g:i A", strtotime($row["created_at"])) . "</div>";
    }

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
