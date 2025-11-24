<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$username = $_GET['client'] ?? $_GET['username'] ?? '';

if (!$username) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([":name" => $username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT message, sender_type, created_at, media_url, media_type
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([":cid" => $client_id]);

$messages = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $messages[] = [
        "message" => $row["message"],
        "sender_type" => $row["sender_type"],
        "created_at" => date("M d g:i A", strtotime($row["created_at"])),
        "media_url" => $row["media_url"],
        "media_type" => $row["media_type"]
    ];
}

echo json_encode($messages);
?>
