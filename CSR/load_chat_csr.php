<?php
// CSR/load_chat_csr.php
session_start();
require_once "../db_connect.php";
header("Content-Type: application/json");

date_default_timezone_set("Asia/Manila");

$client_id = isset($_GET["client_id"]) ? (int)$_GET["client_id"] : 0;
if (!$client_id) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT id,
           message,
           sender_type,
           created_at,
           media_url,
           media_type
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC, id ASC
";
$stmt = $conn->prepare($sql);
$stmt->execute([":cid" => $client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = [];
foreach ($rows as $m) {
    $out[] = [
        "id"          => $m["id"],
        "message"     => $m["message"],
        "sender_type" => $m["sender_type"],
        "created_at"  => $m["created_at"]
            ? date("M j, g:i A", strtotime($m["created_at"]))
            : "",
        "media_url"   => $m["media_url"],
        "media_type"  => $m["media_type"],
    ];
}

echo json_encode($out);
