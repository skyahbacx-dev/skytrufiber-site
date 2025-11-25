<?php
include "../db_connect.php";

$id = (int)($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT full_name, email, district, barangay FROM users WHERE id = :id");
$stmt->execute([":id" => $id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
?>
