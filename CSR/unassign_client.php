<?php
session_start();
include "../db_connect.php";

$cid = $_POST["client_id"] ?? 0;
$csr = $_SESSION["csr_user"] ?? "";

if(!$cid || !$csr) exit;

$stmt = $conn->prepare("
   UPDATE clients
   SET assigned_csr = NULL
   WHERE id = :id AND assigned_csr = :csr
");
$stmt->execute([":id"=>$cid, ":csr"=>$csr]);
echo "ok";
