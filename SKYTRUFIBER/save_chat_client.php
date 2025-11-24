<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$message  = trim($_POST["message"] ?? "");
$username = $_POST["username"] ?? "";

if (!$username) {
    echo json_encode(["status" => "error"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([":name" => $username]);
$client_id = $stmt->fetchColumn();

if (!$client_id) {
    echo json_encode(["status" => "error"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, created_at)
    VALUES (:cid, 'client', :msg, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message
]);

echo json_encode(["status" => "ok"]);
?>
