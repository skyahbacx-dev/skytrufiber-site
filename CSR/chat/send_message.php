<?php
// Ensure clean output, disable warnings printing into JSON
error_reporting(0);
if (!isset($_SESSION)) session_start();

// Correct db path (based on your server structure from error)
require "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

// Get data
$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = $_POST["client_id"] ?? null;
$message  = isset($_POST["message"]) ? trim($_POST["message"]) : "";

// Basic validation
if (!$csrUser || !$clientID || $message === "") {
    echo json_encode([
        "status" => "error",
        "msg"    => "Missing required data"
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (?, 'csr', ?, 0, 0, NOW())
    ");
    $stmt->execute([$clientID, $message]);

    echo json_encode(["status" => "ok"]);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "msg"    => $e->getMessage()
    ]);
    exit;
}
