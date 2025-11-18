<?php
// CSR/assign_client.php
session_start();
require_once "../db_connect.php";
header("Content-Type: application/json");

$csrUser = $_SESSION["csr_user"] ?? "";
$clientId = isset($_POST["client_id"]) ? (int)$_POST["client_id"] : 0;

if (!$csrUser || !$clientId) {
    echo json_encode(["status" => "error"]);
    exit;
}

$sql = "UPDATE clients SET assigned_csr = :csr WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([":csr" => $csrUser, ":id" => $clientId]);

echo json_encode(["status" => "ok"]);
