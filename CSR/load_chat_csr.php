<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");

$client_id = $_GET["client_id"] ?? 0;
if (!$client_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT message, sender_type, created_at, media_path, media_type, csr_fullname
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([":cid" => $client_id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$data = [];

foreach ($rows as $m) {
    $data[] = [
        "message"     => $m["message"],
        "sender_type" => $m["sender_type"],
        "created_at"  => date("M d g:i A", strtotime($m["created_at"])),
        "media_path"  => $m["media_path"],
        "media_type"  => $m["media_type"],
        "csr_fullname"=> $m["csr_fullname"]
    ];
}

echo json_encode($data);
