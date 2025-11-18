<?php
session_start();
include "../db_connect.php";

$csr = $_SESSION["csr_user"];
$id  = $_POST["id"];

$stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id");
$stmt->execute([":csr"=>$csr, ":id"=>$id]);

echo "ok";
