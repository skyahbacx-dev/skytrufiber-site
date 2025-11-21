<?php 
session_start();
include "../db_connect.php";
header("Content-Type: application/json");
date_default_timezone_set("Asia/Manila");

$client_id = $_GET["client_id"] ?? 0;
if (!$client_id) { echo json_encode([]); exit; }

$csrUser = $_SESSION["csr_user"] ?? "";

/* Fetch messages including seen status */
$stmt = $conn->prepare("
    SELECT id, message, sender_type, media_url, media_type, created_at,
    seen
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([":cid" => $client_id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$out = [];

foreach ($rows as $row) {
    $out[] = [
        "id"          => $row["id"],
        "message"     => $row["message"],
        "sender_type" => $row["sender_type"],
        "media_url"   => $row["media_url"],
        "media_type"  => $row["media_type"],
        "seen"        => intval($row["seen"]),
        "created_at"  => date("M d, g:i A", strtotime($row["created_at"]))
    ];
}

echo json_encode($out);
?>
