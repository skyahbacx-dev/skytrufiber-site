<?php
session_start();
include "../db_connect.php";

$id = $_POST["id"];
$stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :id");
$stmt->execute([":id"=>$id]);

echo "ok";
