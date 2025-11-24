<?php
include '../db_connect.php';
header('Content-Type: application/json');
date_default_timezone_set("Asia/Manila");

$username = $_GET['client'] ?? $_GET['username'] ?? null;

if (!$username) {
    echo json_encode([]);
    exit;
}

// Find client record
$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([":name" => $username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT c.id AS chat_id,
           c.sender_type,
           c.message,
           c.created_at,
           m.media_path,
           m.media_type
    FROM chat c
    LEFT JOIN chat_media m ON m.chat_id = c.id
    WHERE c.client_id = :cid
    ORDER BY c.created_at ASC
";
$stmt = $conn->prepare($sql);
$stmt->execute([":cid" => $client_id]);

$messages = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = [
        "chat_id"     => $row["chat_id"],
        "sender_type" => $row["sender_type"],
        "message"     => $row["message"],
        "media_path"  => $row["media_path"],
        "media_type"  => $row["media_type"],
        "created_at"  => date("M d h:i A", strtotime($row["created_at"]))
    ];
}

// Mark unread as seen (client side)
$conn->prepare("
    UPDATE chat
    SET seen = true
    WHERE client_id = :cid AND sender_type = 'csr'
")->execute([":cid" => $client_id]);

echo json_encode($messages);
exit;
?>
