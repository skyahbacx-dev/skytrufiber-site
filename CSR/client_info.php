<?php
include '../db_connect.php';

$id = $_GET["id"] ?? 0;

$stmt = $conn->prepare("SELECT full_name,email,district,barangay FROM users WHERE id=?");
$stmt->execute([$id]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "name" => $u["full_name"],
    "email" => $u["email"],
    "district" => $u["district"],
    "barangay" => $u["barangay"],
    "avatar" => "upload/default-avatar.png"
]);
