<?php
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = (int)($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT name, email, district, barangay FROM clients WHERE id = :cid LIMIT 1");
$stmt->execute([":cid" => $client_id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "name"     => $row["name"] ?? "",
    "email"    => $row["email"] ?? "",
    "district" => $row["district"] ?? "",
    "barangay" => $row["barangay"] ?? ""
]);
?>
