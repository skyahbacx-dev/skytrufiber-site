<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$csr = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_POST["client_id"] ?? 0);

if (!$csr || !$client_id) {
    echo json_encode(["status" => "error"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE clients SET assigned_csr = NULL WHERE id = :id
");
$stmt->execute([":id" => $client_id]);

echo json_encode(["status" => "ok"]);
exit;
