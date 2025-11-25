<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$csr_user = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_POST["client_id"] ?? 0);

if (!$csr_user || !$client_id) {
    echo json_encode(["status" => "error", "msg" => "Invalid Session or Client ID"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE users
        SET assigned_csr = NULL
        WHERE id = :id AND assigned_csr = :csr
    ");
    $stmt->execute([
        ":id"  => $client_id,
        ":csr" => $csr_user
    ]);

    echo json_encode(["status" => "ok", "msg" => "Client Unassigned"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "db_error", "msg" => $e->getMessage()]);
}
exit;
