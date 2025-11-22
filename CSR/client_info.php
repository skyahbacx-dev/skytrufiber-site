<?php
session_start();
include "../db_connect.php";

$client_id = $_GET["id"] ?? 0;
if (!$client_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT name, email, district, barangay
    FROM clients
    WHERE id = :id
");
$stmt->execute([":id" => $client_id]);

$info = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "name"     => $info["name"]     ?? "",
    "email"    => $info["email"]    ?? "",
    "district" => $info["district"] ?? "",
    "barangay" => $info["barangay"] ?? ""
]);
?>
