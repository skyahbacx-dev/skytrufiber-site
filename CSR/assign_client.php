<?php
session_start();
include "../db_connect.php";

$csr = $_SESSION["csr_user"] ?? null;
$client_id = intval($_POST["client_id"] ?? 0);

if (!$csr || !$client_id) exit("ERROR");

$stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id");
$stmt->execute([
    ":csr" => $csr,
    ":id" => $client_id
]);

echo "ok";
exit;
