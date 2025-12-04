<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
$message  = trim($_POST["message"] ?? "");

// --------------------------------------
// VALIDATE USER
// --------------------------------------
if ($username === "") {
    echo json_encode(["status" => "error", "msg" => "missing username"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id 
    FROM users
    WHERE email ILIKE ?
       OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "msg" => "invalid user"]);
    exit;
}

$client_id = $user["id"];

// --------------------------------------
// PREVENT EMPTY TEXT *ONLY IF* no media
// (Media messages handled by upload_media_client.php)
// --------------------------------------
if ($message === "") {
    echo json_encode(["status" => "skip", "msg" => "empty text"]);
    exit;
}

// --------------------------------------
// INSERT MESSAGE
// --------------------------------------
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$stmt->execute([$client_id, $message]);

echo json_encode(["status" => "ok"]);
exit;
