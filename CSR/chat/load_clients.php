<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
if (!$csrUser) {
    echo json_encode(["status" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    // Load ALL clients from main users table
    $stmt = $conn->prepare("
        SELECT id, full_name, email, assigned_csr, is_online
        FROM users
        ORDER BY full_name ASC
    ");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => true, "clients" => $clients]);
} catch (PDOException $e) {
    echo json_encode(["status" => false, "message" => $e->getMessage()]);
}
