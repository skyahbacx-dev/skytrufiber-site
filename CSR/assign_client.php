<?php
include "../db_connect.php";
session_start();

$csr = $_SESSION["csr_fullname"] ?? $_SESSION["csr_user"];
$client_id = $_POST["client_id"];

$stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id");
$stmt->execute([":csr"=>$csr, ":id"=>$client_id]);

echo "ok";
?>
