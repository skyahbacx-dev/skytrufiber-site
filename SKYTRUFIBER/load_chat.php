<?php
include "../db_connect.php";
header("Content-Type: application/json");

$username = $_GET["client"] ?? "";
$stmt = $conn->prepare("
SELECT c.id FROM clients c WHERE c.name = :u LIMIT 1
");
$stmt->execute([":u"=>$username]);
$cid = $stmt->fetchColumn();

$stmt = $conn->prepare("
SELECT message, sender_type, created_at, file_path
FROM chat WHERE client_id = :cid ORDER BY created_at ASC
");
$stmt->execute([":cid"=>$cid]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
