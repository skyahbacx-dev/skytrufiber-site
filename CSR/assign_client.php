<?php
session_start();
include "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_POST["client_id"] ?? 0);

if (!$csrUser || !$client_id) exit("fail");

$conn->prepare("UPDATE users SET assigned_csr = :csr WHERE id = :id")
     ->execute([":csr" => $csrUser, ":id" => $client_id]);

echo "ok";
?>
