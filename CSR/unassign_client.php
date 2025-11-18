<?php
session_start();
require "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
$client_id = $_POST["client_id"] ?? 0;

if (!$csrUser || !$client_id) die("error");

$stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :id");
$stmt->execute([":id" => $client_id]);

echo "unassigned";
?>
