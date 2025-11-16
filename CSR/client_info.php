<?php
include "../db_connect.php";

$client_id = $_GET["id"] ?? 0;
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = :id LIMIT 1");
$stmt->execute([":id" => $client_id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "name"     => $c["name"],
    "email"    => $c["email"],
    "district" => $c["district"],
    "barangay" => $c["barangay"],
    "phone"    => $c["contact"],
]);
?>
