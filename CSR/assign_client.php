<?php
session_start();
include "../db_connect.php";

$csr = $_SESSION["csr_user"] ?? "";
$client_id = $_POST["client_id"] ?? 0;

if (!$csr || !$client_id) {
    echo "error";
    exit;
}

$stmt = $conn->prepare("
    UPDATE clients
    SET assigned_csr = :csr
    WHERE id = :id
");
$stmt->execute([
    ":csr" => $csr,
    ":id" => $client_id
]);

echo "ok";
?>
