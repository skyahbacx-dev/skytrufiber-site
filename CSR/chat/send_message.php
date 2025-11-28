<?php
ob_clean();
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json");

$csrUser  = $_SESSION["csr_user"] ?? null;   // CSR username / ID
$clientID = $_POST["client_id"] ?? null;     // Selected client ID
$message  = trim($_POST["message"] ?? "");   // Typed message

// Validate input
if (!$csrUser || !$clientID || !$message) {
    echo json_encode([
        "status" => "error",
        "msg" => "Required data missing"
    ]);
    exit;
}

try {
    // 🔍 Check if the client is assigned to a CSR
    $assignCheck = $conn->prepare("
        SELECT assigned_to
        FROM client_assignments
        WHERE client_id = ?
    ");
    $assignCheck->execute([$clientID]);
    $assignedCSR = $assignCheck->fetchColumn();

    // 🔒 If assigned to another CSR, block
    if ($assignedCSR && $assignedCSR !== $csrUser) {
        echo json_encode(["status" => "locked"]);
        exit;
    }

    // 💬 Insert message
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (:cid, 'csr', :msg, 0, 0, NOW())
    ");
    $stmt->execute([
        ":cid" => $clientID,
        ":msg" => $message
    ]);

    // Return success JSON response
    echo json_encode(["status" => "ok"]);
    exit;

} catch (PDOException $e) {

    // Return detailed error
    echo json_encode([
        "status" => "error",
        "msg" => $e->getMessage()
    ]);
    exit;
}
