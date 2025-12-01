<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;
$message  = $_POST["message"] ?? "";

if (!$username) {
    echo json_encode(["status" => "error", "msg" => "Missing username"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$client) exit(json_encode(["status" => "error", "msg" => "Invalid user"]));

$client_id = (int)$client["id"];

$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$client_id, $message]);

echo json_encode(["status" => "ok"]);
exit;
?>
