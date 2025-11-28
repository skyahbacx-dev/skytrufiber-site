<?php
// send_message.php - stable JSON version

if (!isset($_SESSION)) session_start();

// IMPORTANT: this path should match where db_connect.php actually is.
// From /app/CSR/chat/send_message.php going two levels up => /app/db_connect.php
require "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

// Logged in CSR username from session
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
    // Insert CSR message into chat table
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (?, 'csr', ?, 0, 0, NOW())
    ");
    $stmt->execute([$clientID, $message]);

    // Success JSON
    echo json_encode(["status" => "ok"]);
    exit;

} catch (PDOException $e) {

    // DB error JSON (visible in Network → Response)
    echo json_encode([
        "status" => "error",
        "msg"    => $e->getMessage()
    ]);
    exit;
}
