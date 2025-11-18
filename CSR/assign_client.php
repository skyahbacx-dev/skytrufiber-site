<?php
session_start();
include "../db_connect.php";

$cid = (int)($_POST["client_id"] ?? 0);
$csr = $_SESSION["csr_user"] ?? "";

if (!$cid || !$csr) { echo "error"; exit; }

$stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id");
$stmt->execute([":csr" => $csr, ":id" => $cid]);

echo "ok";
?>
