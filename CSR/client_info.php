<?php
include "../db_connect.php";

$id = $_GET["id"];

$stmt = $conn->prepare("
SELECT c.name,c.district,c.barangay, u.email
FROM clients c
LEFT JOIN users u ON u.account_number = c.account_number
WHERE c.id=:id LIMIT 1
");
$stmt->execute([":id"=>$id]);
$c=$stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($c);
