<?php
session_start();
include "../db_connect.php";

$cid = $_POST["client_id"];
$csr = $_SESSION["csr_user"];

$stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :cid");
$stmt->execute([":csr" => $csr, ":cid" => $cid]);
echo "ok";
