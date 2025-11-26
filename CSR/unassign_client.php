<?php
session_start();
require "../db_config.php";

if (!isset($_SESSION['csr_user'])) {
    exit(json_encode(["status" => "error", "message" => "Unauthorized"]));
}

$clientId = $_POST['client_id'] ?? null;

if (!$clientId) {
    exit(json_encode(["status" => "error", "message" => "Missing client ID"]));
}

try {
    $sql = "UPDATE users SET assigned_csr = NULL WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $clientId]);

    echo json_encode(["status" => "success"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
