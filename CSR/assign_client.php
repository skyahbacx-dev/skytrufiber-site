<?php
session_start();
include "../db_connect.php";

$cid = $_POST["client_id"] ?? 0;
$csr = $_SESSION["csr_user"] ?? "";

if(!$cid || !$csr){ exit; }

$stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id");
$stmt->execute([":csr"=>$csr, ":id"=>$cid]);
echo "ok";
