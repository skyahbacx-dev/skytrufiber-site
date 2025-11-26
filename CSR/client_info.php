<?php
include "../db_connect.php";

$id = $_GET["id"] ?? 0;

$sql = "SELECT full_name,email,district,barangay,assigned_csr FROM users WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => $id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
?>
