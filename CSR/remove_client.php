<?php
include "../db_connect.php";
session_start();

$client_id = $_POST["client_id"];

$stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :id");
$stmt->execute([":id"=>$client_id]);

echo "ok";
?>
