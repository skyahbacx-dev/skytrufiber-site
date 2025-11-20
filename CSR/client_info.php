<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$client_id = $_GET["id"] ?? 0;

if (!$client_id) {
    echo json_encode(["error" => "Missing ID"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT name, email, district, barangay
    FROM clients 
    WHERE id = :id
");
$stmt->execute([":id" => $client_id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo json_encode([
        "name"     => $row["name"],
        "email"    => $row["email"],
        "district" => $row["district"],
        "barangay" => $row["barangay"]
    ]);
} else {
    echo json_encode(["error" => "Client not found"]);
}
?>
