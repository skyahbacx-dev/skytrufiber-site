<?php
session_start();
include "../db_connect.php";

header("Content-Type: application/json");

$csrUser = $_SESSION["csr_user"] ?? null;
$client_id = intval($_POST["client_id"] ?? 0);

if (!$csrUser || !$client_id) {
    echo json_encode(["status" => "error", "msg" => "Invalid request"]);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :id");
    $stmt->execute([
        ":csr" => $csrUser,
        ":id"  => $client_id
    ]);

    echo json_encode(["status" => "ok"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
exit;
