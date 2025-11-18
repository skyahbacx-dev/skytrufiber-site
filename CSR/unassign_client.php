<?php
session_start();
include "../db_connect.php";

$id  = $_POST["client_id"] ?? 0;
$csr = $_SESSION["csr_user"] ?? "";

if (!$id || !$csr) exit;

$stmt = $conn->prepare("
  UPDATE clients
  SET assigned_csr = NULL
  WHERE id = :id AND assigned_csr = :csr
");
$stmt->execute([":id"=>$id, ":csr"=>$csr]);

echo "ok";
