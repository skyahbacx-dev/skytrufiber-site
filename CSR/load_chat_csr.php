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
$response = [];

foreach ($rows as $r) {
    $response[] = [
        "message"     => $r["message"],
        "sender_type" => $r["sender_type"],
        "media_path"  => $r["media_path"],
        "media_type"  => $r["media_type"],
        "csr_fullname"=> $r["csr_fullname"],
        "created_at"  => date("M d g:i A", strtotime($r["created_at"]))
    ];
}

echo json_encode($response);
exit;
?>
