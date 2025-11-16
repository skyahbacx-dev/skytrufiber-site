<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

date_default_timezone_set("Asia/Manila");

$client_id = $_GET["client_id"] ?? 0;
if (!$client_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, message, sender_type, created_at, media_path, media_type, assigned_csr, csr_fullname, is_seen
    FROM chat
    WHERE client_id = :cid
    ORDER BY id ASC
");
$stmt->execute([":cid" => $client_id]);

$result = [];
while ($m = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $result[] = [
        "id"          => $m["id"],
        "message"     => $m["message"],
        "sender_type" => $m["sender_type"],
        "created_at"  => date("M d g:i A", strtotime($m["created_at"])),
        "file_path"   => $m["media_path"],   // 👈 Match JS key
        "file_type"   => $m["media_type"],   // 👈 Match JS key
        "assigned_csr"=> $m["assigned_csr"],
        "csr_fullname"=> $m["csr_fullname"],
        "is_seen"     => $m["is_seen"] ?? 0
    ];
}

echo json_encode($result);
?>
