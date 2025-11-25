<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

$user_id = $_SESSION["user"] ?? null;
if (!$user_id) { echo json_encode([]); exit; }

$sql = "
    SELECT c.id AS chat_id, c.sender_type, c.message, c.created_at, c.seen,
           m.media_path, m.media_type
    FROM chat c
    LEFT JOIN chat_media m ON m.chat_id = c.id
    WHERE c.client_id = :cid
    ORDER BY c.created_at ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([":cid" => $user_id]);

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data[] = [
        "chat_id"     => $row["chat_id"],
        "sender_type" => $row["sender_type"],
        "message"     => $row["message"],
        "media_path"  => $row["media_path"],
        "media_type"  => $row["media_type"],
        "created_at"  => date("M d h:i A", strtotime($row["created_at"])),
        "seen"        => $row["seen"]
    ];
}

$conn->prepare("
    UPDATE chat SET delivered = true
    WHERE client_id = :id AND delivered = false
")->execute([":id" => $user_id]);

echo json_encode($data);
exit;
?>
