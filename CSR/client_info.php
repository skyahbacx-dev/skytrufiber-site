<?php
include "../db_connect.php";

$id = intval($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT full_name, email, district, barangay, assigned_csr FROM users WHERE id = :id");
$stmt->execute(["id" => $id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "name"      => $data["full_name"] ?? "",
    "email"     => $data["email"] ?? "",
    "district"  => $data["district"] ?? "",
    "barangay"  => $data["barangay"] ?? "",
    "assigned"  => $data["assigned_csr"] ?? null
]);
