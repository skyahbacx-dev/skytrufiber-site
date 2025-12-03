<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

ini_set("display_errors", 1);
error_reporting(E_ALL);

$username = $_POST["username"] ?? null;
$message  = trim($_POST["message"] ?? "");

if (!$username) {
    echo json_encode(["status" => "error", "msg" => "Missing username"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id FROM users
    WHERE email = ? OR full_name = ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "msg" => "User not found"]);
    exit;
}

$client_id = (int)$user["id"];

// Empty message (allowed only if media attached)
if ($message === "") {
    echo json_encode(["status" => "skip", "msg" => "Empty text skipped"]);
    exit;
}

$ins = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$ok = $ins->execute([$client_id, $message]);

if (!$ok) {
    echo json_encode(["status" => "error", "msg" => "Database error"]);
    exit;
}

echo json_encode([
    "status" => "ok",
    "chat_id" => $conn->lastInsertId()
]);
exit;
