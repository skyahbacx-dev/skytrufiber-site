<?php
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr       = $_POST["csr"] ?? null;

if (!$client_id || !$csr) exit("Missing");

$stmt = $conn->prepare("UPDATE users SET assigned_csr = ? WHERE id = ?");
$stmt->execute([$csr, $client_id]);

echo "OK";
