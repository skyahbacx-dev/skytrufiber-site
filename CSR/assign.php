<?php
include "../db_connect.php";
session_start();

$csr = $_SESSION["csr_user"];
$client = $_POST["client_id"];

$sql = "UPDATE users SET assigned_csr = :csr WHERE id = :cid";
$stmt = $pdo->prepare($sql);
$stmt->execute([":csr" => $csr, ":cid" => $client]);

echo "ok";
?>
