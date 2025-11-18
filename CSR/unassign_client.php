<?php
// CSR/unassign_client.php
session_start();
require_once "../db_connect.php";
header("Content-Type: application/json");

$csrUser = $_SESSION["csr_user"] ?? "";
$clientId = isset($_POST["client_id"]) ? (int)$_POST["client_id"] : 0;

if (!$csrUser || !$clientId) {
    echo json_encode(["status" => "error"]);
    exit;
}

// Only allow unassign if this CSR currently owns it
$sql = "UPDATE clients SET assigned_csr = NULL WHERE id = :id AND assigned_csr = :csr";
$stmt = $conn->prepare($sql);
$stmt->execute([":id" => $clientId, ":csr" => $csrUser]);

echo json_encode(["status" => "ok"]);
