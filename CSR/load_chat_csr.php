<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = $_GET["client_id"] ?? 0;
if (!$client_id) { echo json_encode([]); exit; }

$stmt = $conn->prepare("
    SELECT message, sender_type, media_path, media_type, created_at
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([":cid" => $client_id]);

$list = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $list[] = [
        "message"     => $r["message"],
        "sender_type" => $r["sender_type"],
        "media_path"  => $r["media_path"],
        "media_type"  => $r["media_type"],
        "created_at"  => date("M d g:i A", strtotime($r["created_at"]))
    ];
}

echo json_encode($list);
?>
