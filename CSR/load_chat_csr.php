<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = intval($_GET["client_id"] ?? 0);

if (!$client_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.message,
        c.sender_type,
        c.csr_fullname,
        c.created_at,
        m.media_path,
        m.media_type
    FROM chat c
    LEFT JOIN chat_media m ON m.chat_id = c.id
    WHERE c.client_id = :cid
    ORDER BY c.created_at ASC, m.id ASC
");

$stmt->execute([":cid" => $client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$messages = [];
foreach ($rows as $row) {
    $messages[] = [
        "id"          => $row["id"],
        "message"     => $row["message"],
        "sender_type" => $row["sender_type"],
        "csr_fullname"=> $row["csr_fullname"],
        "created_at"  => date("M d g:i A", strtotime($row["created_at"])),
        "media_path"  => $row["media_path"],
        "media_type"  => $row["media_type"]
    ];
}

echo json_encode($messages);
