<?php
include "../db_connect.php";
$user_id = (int)($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT typing FROM users WHERE id = :id");
$stmt->execute([":id" => $user_id]);

echo json_encode(["typing" => $stmt->fetchColumn()]);
?>
