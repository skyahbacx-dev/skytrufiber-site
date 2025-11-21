<?php
session_start();
include "../db_connect.php";

$client_id = $_POST["client_id"] ?? 0;
if (!$client_id) exit("error");

$stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :id");
$stmt->execute([":id" => $client_id]);

echo "ok";
?>
