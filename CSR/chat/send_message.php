<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = $_POST["client_id"] ?? null;
$message  = trim($_POST["message"] ?? "");

if (!$csrUser || !$clientID || $message === "") {
    echo json_encode(["status" => "error", "msg" => "Missing data"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
        VALUES (:cid, 'csr', :msg, FALSE, FALSE, NOW())
    ");
    $stmt->execute([
        ":cid" => $clientID,
        ":msg" => $message
    ]);

    echo json_encode(["status" => "ok"]);
    exit;

} catch (Throwable $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
    exit;
}
