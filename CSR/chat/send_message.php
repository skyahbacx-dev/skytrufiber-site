<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr       = $_POST["csr"] ?? null;
$message   = trim($_POST["message"] ?? "");

if (!$client_id || !$csr || $message === "") {
    echo "Missing data";
    exit;
}

try {
    // Insert message
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (?, 'csr', ?, TRUE, FALSE, NOW())
    ");
    $stmt->execute([$client_id, $message]);

    echo "OK";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
