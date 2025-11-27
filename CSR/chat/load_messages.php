<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST['client_id'] ?? null;

if (!$client_id) {
    echo "<div class='no-chat'>Select a client to start chat.</div>";
    exit;
}

// Fetch chat messages
$query = $conn->prepare("
    SELECT c.id, c.sender_type, c.message, c.created_at, c.seen, c.delivered,
        m.media_path, m.media_type
    FROM chat c
    LEFT JOIN chat_media m ON m.chat_id = c.id
    WHERE c.client_id = ?
    ORDER BY c.created_at ASC
");
$query->execute([$client_id]);
$messages = $query->fetchAll(PDO::FETCH_ASSOC);

if (!$messages) {
    echo "<div class='no-chat'>No messages yet — start the conversation.</div>";
    exit;
}

foreach ($messages as $msg) {
    $isCSR = ($msg['sender_type'] === 'csr');

    // Choose bubble side
    $class = $isCSR ? "bubble-csr" : "bubble-client";

    // Convert timestamp to formatted string
    $time = date("M d, h:i A", strtotime($msg['created_at']));

    echo "<div class='chat-row {$class}'>";

    // Media
    if ($msg['media_path']) {
        $mediaPath = "../../" . $msg['media_path'];

        if ($msg['media_type'] === "image") {
            echo "<img src='{$mediaPath}' class='chat-image'>";
        } elseif ($msg['media_type'] === "video") {
            echo "<video controls class='chat-video'>
                    <source src='{$mediaPath}' type='video/mp4'>
                  </video>";
        } else {
            $fileName = basename($mediaPath);
            echo "<a href='{$mediaPath}' download class='chat-file'><i class='fa fa-file'></i> {$fileName}</a>";
        }
    }

    // Text message
    if ($msg['message']) {
        echo "<p class='msg-text'>" . htmlspecialchars($msg['message']) . "</p>";
    }

    // Status (only for CSR messages)
    if ($isCSR) {
        $status = $msg["seen"] ? "Seen" : ($msg["delivered"] ? "Delivered" : "Sent");
        echo "<span class='status-label'>{$status}</span>";
    }

    echo "<div class='msg-time'>{$time}</div>";
    echo "</div>";
}
?>
