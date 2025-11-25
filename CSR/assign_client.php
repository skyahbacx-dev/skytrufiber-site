<?php
session_start();
include "../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_POST["client_id"] ?? 0);

if (!$csr || !$client_id) {
    echo "INVALID";
    exit;
}

$conn->prepare("UPDATE users SET assigned_csr = :csr WHERE id = :id")
     ->execute([":csr" => $csr, ":id" => $client_id]);

echo "OK";
?>
