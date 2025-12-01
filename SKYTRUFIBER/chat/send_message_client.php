<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;
$message  = trim($_POST["message"] ?? "");

if (!$username) {
    echo json_encode(["status" => "error", "msg" => "Missing username"]);
    exit;
}

// Allow login via email OR full name
$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE email = ? OR full_name = ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    echo json_encode(["status" => "error", "msg" => "Invalid user"]);
    exit;
}

$client_id = (int)$client["id"];

// If no text and no media, stop safely
if ($message === "") {
    echo json_encode(["status" => "ok", "msg" => "Empty text skipped"]);
    exit;
}

// Insert basic chat row
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$client_id, $message]);

$chatId = $conn->lastInsertId();

// Respond with success + message ID
echo json_encode([
    "status" => "ok",
    "chat_id" => $chatId,
    "msg" => "Message sent"
]);
exit;
?>
