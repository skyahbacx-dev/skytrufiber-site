<?php
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = (int)($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT typing FROM clients WHERE id = :id");
$stmt->execute([":id" => $client_id]);

echo json_encode(["typing" => (bool)$stmt->fetchColumn()]);
exit;
