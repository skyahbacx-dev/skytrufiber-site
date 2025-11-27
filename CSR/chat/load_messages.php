<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$clientID = $_POST["client_id"] ?? null;
if (!$clientID) {
    exit("No client selected.");
}

try {
    $stmt = $conn->prepare("
        SELECT sender_type, message, created_at
        FROM chat
        WHERE client_id = :cid
        ORDER BY created_at ASC
    ");
    $stmt->execute([":cid" => $clientID]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$messages) {
        echo "<p style='color:#999; font-style:italic;'>No messages yet.</p>";
        exit;
    }

    foreach ($messages as $m) {
        $sender = $m["sender_type"];
        $msg    = htmlspecialchars($m["message"]);
        $time   = date("M d g:i A", strtotime($m["created_at"]));

        $class = ($sender === "client") ? "msg-client" : "msg-csr";

        echo "
        <div class='chat-bubble $class'>
            <p>$msg</p>
            <span class='chat-time'>$time</span>
        </div>";
    }

} catch (PDOException $e) {
    echo "DB ERROR: " . htmlspecialchars($e->getMessage());
}
