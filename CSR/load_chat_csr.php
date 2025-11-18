<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$cid = $_GET["client_id"] ?? 0;
if(!$cid){ echo "[]"; exit; }

$stmt = $conn->prepare("
SELECT message, sender_type, created_at, media_path, media_type
FROM chat WHERE client_id=:cid ORDER BY created_at ASC
");
$stmt->execute([":cid"=>$cid]);

$out = [];
while($m=$stmt->fetch(PDO::FETCH_ASSOC)){
    $out[] = [
        "message"=>$m["message"],
        "sender_type"=>$m["sender_type"],
        "created_at"=>date("M d, g:i A", strtotime($m["created_at"])),
        "media_path"=>$m["media_path"],
        "media_type"=>$m["media_type"]
    ];
}
echo json_encode($out);
