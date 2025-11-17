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
        created_at,
        media_path,
        media_type,
        csr_fullname
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC
");
$stmt->execute([":cid" => $client_id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$output = [];

foreach ($rows as $row) {

    $output[] = [
        "message"     => $row["message"],
        "sender_type" => $row["sender_type"],        // IMPORTANT for alignment
        "csr_fullname"=> $row["csr_fullname"] ?? "",
        "created_at"  => date("M d, g:i A", strtotime($row["created_at"])),
        "media_path"  => $row["media_path"],
        "media_type"  => $row["media_type"]
    ];
}

echo json_encode($output);
exit;
?>
