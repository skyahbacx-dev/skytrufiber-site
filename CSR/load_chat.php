<?php
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = $_GET["client_id"] ?? 0;

$stmt = $conn->prepare("SELECT message, sender_type, created_at, file_path FROM chat WHERE client_id = :id ORDER BY created_at ASC");
$stmt->execute([":id"=>$client_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
