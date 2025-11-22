<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

$client_id = $_GET["client_id"] ?? 0;
if (!$client_id) { echo json_encode([]); exit; }

$stmt = $conn->prepare("
    SELECT c.id, c.message, c.sender_type, c.csr_fullname,
           m.media_path AS media_url, m.media_type AS media_type,
           c.created_at
    FROM chat c
    LEFT JOIN chat_media m ON c.id = m.chat_id
    WHERE c.client_id = :cid
    ORDER BY c.created_at ASC
");
$stmt->execute([":cid" => $client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = [];
foreach ($rows as $r) {
    $out[] = [
        "id"          => $r["id"],
        "message"     => $r["message"],
        "sender_type" => $r["sender_type"],
        "media_url"   => $r["media_url"],
        "media_type"  => $r["media_type"],
        "created_at"  => date("M d, g:i A", strtotime($r["created_at"]))
    ];
}

echo json_encode($out);
?>
