<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = (int)($_GET["client_id"] ?? 0);
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
        media_type
    FROM chat
    WHERE client_id = :cid
    ORDER BY created_at ASC, id ASC
");
$stmt->execute([":cid" => $client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = [];
foreach ($rows as $m) {
    $out[] = [
        "message"     => $m["message
