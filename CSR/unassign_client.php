<?php
session_start();
require "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? "";
$client_id = $_POST["client_id"] ?? 0;

if ($csrUser && $client_id) {
    $stmt = $conn->prepare("UPDATE clients SET assigned_csr = NULL WHERE id = :id AND assigned_csr = :csr");
    $stmt->execute([":id" => $client_id, ":csr" => $csrUser]);
}

echo "ok";
?>
