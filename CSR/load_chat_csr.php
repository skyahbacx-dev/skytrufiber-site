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

/*
   Pull messages + media in one structured array:
   Each chat row can have 0..n media rows from chat_media table.
*/

$stmt = $conn->prepare("
    SELECT
        c.id AS chat_id,
        c.message,
        c.sender_type,
        c.created_at,
        c.csr_fullname,
        COALESCE(json_agg(
            json_build_object(
                'media_path', cm.media_path,
                'media_type', cm.media_type
            )
        ) FILTER (WHERE cm.id IS NOT NULL), '[]') AS media_files
    FROM chat c
    LEFT JOIN chat_media cm ON cm.chat_id = c.id
    WHERE c.client_id = :cid
    GROUP BY c.id
    ORDER BY c.created_at ASC
");

$stmt->execute([":cid" => $client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = [];
foreach ($rows as $row) {

    $out[] = [
        "chat_id"     => $row["chat_id"],
        "message"     => $row["message"],
        "sender_type" => $row["sender_type"],
        "created_at"  => date("M d, g:i A", strtotime($row["created_at"])),
        "csr_fullname"=> $row["csr_fullname"],
        "media_files" => json_decode($row["media_files"], true)
    ];
}

echo json_encode($out);
?>
