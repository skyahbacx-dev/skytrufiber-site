<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$clientID = $_POST["client_id"] ?? null;
if (!$clientID) {
    exit("No client selected.");
}

try {
    $stmt = $conn->prepare("
        SELECT c.id, c.sender_type, c.message, c.created_at,
               m.media_path, m.media_type
        FROM chat c
        LEFT JOIN chat_media m ON c.id = m.chat_id
        WHERE c.client_id = :cid
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([":cid" => $clientID]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$messages) {
        echo "<p style='color:#999; font-style:italic;'>No messages yet.</p>";
        exit;
    }

    foreach ($messages as $m) {
        $sender = $m["sender_type"];
        $msg    = htmlspecialchars($m["message"] ?? "");
        $time   = date("M d g:i A", strtotime($m["created_at"]));
        $media  = $m["media_path"];
        $mtype  = $m["media_type"];

        $class = ($sender === "client") ? "msg-client" : "msg-csr";

        echo "<div class='chat-bubble $class'>";

        // TEXT
        if (!empty($msg)) {
            echo "<p>$msg</p>";
        }

        // MEDIA PREVIEW
        if (!empty($media)) {
            if ($mtype === "image") {
                echo "<img src='../../$media' class='chat-img' onclick='window.open(\"../../$media\")'>";
            } elseif ($mtype === "video") {
                echo "<video class='chat-video' controls><source src='../../$media'></video>";
            } else {
                echo "<a href='../../$media' download class='chat-file'>📎 Download File</a>";
            }
        }

        echo "<span class='chat-time'>$time</span>";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "DB ERROR: " . htmlspecialchars($e->getMessage());
}
