<?php
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;

if (!$client_id) {
    echo "<p>No client selected.</p>";
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.sender_type,
            c.message,
            c.created_at,
            m.media_path,
            m.media_type
        FROM chat c
        LEFT JOIN chat_media m ON m.chat_id = c.id
        WHERE c.client_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$client_id]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $isCSR = ($row["sender_type"] === "csr");
        $class = $isCSR ? "msg-csr" : "msg-client";

        echo "<div class='msg-bubble $class'>";

        // TEXT MESSAGE (if exists)
        if (!empty($row["message"])) {
            echo "<p>" . nl2br(htmlspecialchars($row["message"])) . "</p>";
        }

        // MEDIA MESSAGE (if exists)
        if (!empty($row["media_path"])) {

            if ($row["media_type"] === "image") {
                echo "<img src='../../" . $row["media_path"] . "' class='chat-image' onclick='openImageViewer(this.src)'>";
            } else {
                echo "<a href='../../" . $row["media_path"] . "' target='_blank' class='file-attachment'>📎 Download File</a>";
            }
        }

        echo "<span class='time'>" . date("h:i A", strtotime($row["created_at"])) . "</span>";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}
