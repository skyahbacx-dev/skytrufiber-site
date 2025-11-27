<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$csr       = $_SESSION["csr_user"] ?? null;
$client_id = $_POST["client_id"] ?? null;
$message   = trim($_POST["message"] ?? "");

if (!$csr || !$client_id || $message === "") {
    echo "Missing";
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (:cid, 'csr', :msg, FALSE, FALSE, NOW())
    ");
    $stmt->execute([
        ":cid" => $client_id,
        ":msg" => $message
    ]);

    echo "OK";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
