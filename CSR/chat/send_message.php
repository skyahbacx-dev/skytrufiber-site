<?php
// Clean output buffer to avoid whitespace issues
ob_clean();
if (!isset($_SESSION)) session_start();

// Correct DB path (update based on your structure)
require "../db_connect.php";
// If db_connect.php is in root instead, use:
// require "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

// Logged in CSR username
$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = $_POST["client_id"] ?? null;
$message  = isset($_POST["message"]) ? trim($_POST["message"]) : "";

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

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
    exit;
}
