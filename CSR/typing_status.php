<?php
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = (int)($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT typing FROM typing_status WHERE user_id = :id LIMIT 1");
$stmt->execute([":id" => $client_id]);

echo json_encode(["typing" => (bool)$stmt->fetchColumn()]);
exit;
