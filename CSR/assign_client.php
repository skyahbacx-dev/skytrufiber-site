<?php
session_start();
include "../db_connect.php";

$id  = $_POST["client_id"] ?? 0;
$csr = $_SESSION["csr_user"] ?? "";

if (!$id || !$csr) exit;

$stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id");
$stmt->execute([":csr"=>$csr, ":id"=>$id]);

echo "ok";
