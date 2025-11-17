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
    SELECT 
        message,
        sender_type,
        media_path,
        media_type,
        csr_fullname,
        created_at
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([":cid" => $client_id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$result = [];

foreach ($rows as $m) {
    $result[] = [
        "message"     => $m["message"],
        "sender_type" => $m["sender_type"],
        "media_path"  => $m["media_path"],
        "media_type"  => $m["media_type"],
        "csr_fullname"=> $m["csr_fullname"],
        "created_at"  => date("M d g:i A", strtotime($m["created_at"]))
    ];
}

echo json_encode($result);
?>
