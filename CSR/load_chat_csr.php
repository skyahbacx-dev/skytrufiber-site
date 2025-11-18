<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");

$client_id = $_GET["client_id"] ?? 0;
if (!$client_id) { echo json_encode([]); exit; }

$stmt = $conn->prepare("
    SELECT id, message, sender_type, created_at
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([":cid" => $client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = [];

foreach ($rows as $m) {
    $chat_id = $m["id"];

    $media = $conn->prepare("SELECT media_path, media_type FROM chat_media WHERE chat_id = :cid");
    $media->execute([":cid" => $chat_id]);
    $mediaRows = $media->fetchAll(PDO::FETCH_ASSOC);

    $out[] = [
        "message"     => $m["message"],
        "sender_type" => $m["sender_type"],
        "created_at"  => date("M d g:i A", strtotime($m["created_at"])),
        "media"       => $mediaRows
    ];
}

echo json_encode($out);
?>
