<?php
// send_message.php - minimal, stable version

if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

// Always respond as JSON
header("Content-Type: application/json; charset=utf-8");

// Logged in CSR username (from login)
$csrUser  = $_SESSION["csr_user"] ?? null;

// Data from AJAX
$clientID = $_POST["client_id"] ?? null;
$message  = isset($_POST["message"]) ? trim($_POST["message"]) : "";

// Basic validation
if (!$csrUser || !$clientID || $message === "") {
    echo json_encode([
        "status" => "error",
        "msg"    => "Missing CSR, client, or message"
    ]);
    exit;
}

try {
    // Insert chat row (CSR message)
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (?, 'csr', ?, 0, 0, NOW())
    ");
    $stmt->execute([$clientID, $message]);

    // Success
    echo json_encode(["status" => "ok"]);
    exit;

} catch (PDOException $e) {

    // On DB error, send error JSON (visible in DevTools console)
    echo json_encode([
        "status" => "error",
        "msg"    => $e->getMessage()
    ]);
    exit;
}
