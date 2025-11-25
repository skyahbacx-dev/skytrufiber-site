<?php
include "../db_connect.php";
header("Content-Type: application/json");

$id = intval($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT name, email, district, barangay FROM clients WHERE id = :id LIMIT 1");
$stmt->execute([":id" => $id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($row ?: []);
exit;
