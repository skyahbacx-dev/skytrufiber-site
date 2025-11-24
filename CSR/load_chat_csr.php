<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

$client_id = $_GET["client_id"] ?? 0;
if (!$client_id) { echo json_encode([]); exit; }

/*
    NEW STRUCTURE:
    - chat table stores text / sender / time
    - chat_media contains actual uploaded media files
    - LEFT JOIN to fetch all files related to message
*/
$stmt = $conn->prepare("
    SELECT c.id AS chat_id,
           c.message,
           c.sender_type,
           c.created_at,
           m.media_path,
           m.media_type
    FROM chat c
    LEFT JOIN chat_media m ON m.chat_id = c.id
    WHERE c.client_id = :cid
    ORDER BY c.created_at ASC
");
$stmt->execute([":cid" => $client_id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$messages = [];

foreach ($rows as $row) {

    // Build message entry
    $messages[] = [
        "chat_id"     => $row["chat_id"],
        "message"     => $row["message"],
        "sender_type" => $row["sender_type"],
        "media_url"   => $row["media_path"],      // may be null
        "media_type"  => $row["media_type"],      // may be null
        "created_at"  => date("M d, g:i A", strtotime($row["created_at"]))
    ];
}

echo json_encode($messages);
?>
