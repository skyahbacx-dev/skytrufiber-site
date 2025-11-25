<?php
session_start();
include "../db_connect.php";

$client_id = (int)($_POST["client_id"] ?? 0);

if (!$client_id) exit("fail");

$conn->prepare("UPDATE users SET assigned_csr = NULL WHERE id = :id")
     ->execute([":id" => $client_id]);

echo "ok";
?>
