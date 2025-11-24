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
    SELECT id, message, sender_type, created_at, media_path, media_type, csr_fullname
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([":cid" => $client_id]);

$messages = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = [
        "id"          => $row["id"],
        "message"     => $row["message"],
        "sender_type" => $row["sender_type"],
        "created_at"  => date("M d g:i A", strtotime($row["created_at"])),
        "media_url"   => $row["media_path"], // DIRECT PUBLIC URL from B2
        "media_type"  => $row["media_type"],
        "csr_name"    => $row["csr_fullname"]
    ];
}

echo json_encode($messages);
?>
