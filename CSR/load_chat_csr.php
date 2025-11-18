<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");

$client_id = (int)($_GET["client_id"] ?? 0);
if (!$client_id) { echo json_encode([]); exit; }

$bucketBase = "https://s3.us-east-005.backblazeb2.com/ahba-chat-media/";

$stmt = $conn->prepare("
    SELECT message, sender_type, created_at, media_path, media_type
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([":cid" => $client_id]);

$out = [];
while ($m = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $mediaURL = null;
    if ($m["media_path"]) {
        $mediaURL = $bucketBase . $m["media_path"];
    }

    $out[] = [
        "message"     => $m["message"],
        "sender_type" => $m["sender_type"],
        "created_at"  => date("M d, g:i A", strtotime($m["created_at"])),
        "media_path"  => $mediaURL,
        "media_type"  => $m["media_type"]
    ];
}

echo json_encode($out);
?>
