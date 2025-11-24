<?php
session_start();
include "../db_connect.php";

$csr = $_SESSION['csr_user'] ?? null;
$clientId = $_POST['client_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$csr || !$clientId || !$status) {
    http_response_code(400);
    exit("Invalid request");
}

// remove previous record
$conn->prepare("DELETE FROM typing_status WHERE csr = :csr AND client_id = :client_id")
     ->execute([":csr" => $csr, ":client_id" => $clientId]);

// insert new status entry (if typing)
if ($status === "typing") {
    $stmt = $conn->prepare("
        INSERT INTO typing_status (csr, client_id, is_typing, updated_at)
        VALUES (:csr, :client_id, TRUE, NOW())
    ");
    $stmt->execute([
        ":csr" => $csr,
        ":client_id" => $clientId
    ]);
}

echo "OK";
