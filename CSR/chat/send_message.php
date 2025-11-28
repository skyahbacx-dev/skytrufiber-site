<?php
// No whitespace above PHP tag!

// Disable notice & warning output (protect JSON)
error_reporting(E_ERROR | E_PARSE);
ini_set("display_errors", 0);

if (!isset($_SESSION)) session_start();

// Correct DB path as confirmed from structure
require "../../db_connect.php";

header("Content-Type: application/json");

// Get POST data
$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = $_POST["client_id"] ?? null;
$message  = isset($_POST["message"]) ? trim($_POST["message"]) : "";

// Validate fields
if (!$csrUser || !$clientID || $message === "") {
    echo json_encode(["status" => "error", "msg" => "Missing fields"]);
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
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
    exit;
}
