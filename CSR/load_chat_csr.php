<?php
session_start();
require "../db_connect.php";

$client_id = $_GET["client_id"] ?? 0;
if (!$client_id) die(json_encode([]));

$stmt = $conn->prepare("
    SELECT c.id, c.message, c.sender_type, c.created_at, m.media_path, m.media_type
    FROM chat c
    LEFT JOIN chat_media m ON m.chat_id = c.id
    WHERE c.client_id = :cid
    ORDER BY c.created_at ASC
");
$stmt->execute([":cid" => $client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = [];
foreach ($rows as $m) {
    $out[] = [
        "message" => $m["message"],
        "sender_type" => $m["sender_type"],
        "created_at" => date("M d, g:i A", strtotime($m["created_at"])),
        "media_path" => $m["media_path"],
        "media_type" => $m["media_type"]
    ];
}

echo json_encode($out);
?>
