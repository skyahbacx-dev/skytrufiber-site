<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

$csr_user  = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_GET["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT c.id AS chat_id, c.sender_type, c.message, c.created_at, c.seen, c.delivered,
           m.media_path, m.media_type
    FROM chat c
    LEFT JOIN chat_media m ON c.id = m.chat_id
    WHERE c.user_id = :cid
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
        "created_at"  => date("M d h:i A", strtotime($row["created_at"])),
        "seen"        => $row["seen"] ?? false,
        "delivered"   => $row["delivered"] ?? false
    ];
}

$conn->prepare("
    UPDATE chat
    SET seen = true
    WHERE user_id = :cid AND sender_type = 'client' AND seen = false
")->execute([":cid" => $client_id]);

echo json_encode($messages);
exit;
