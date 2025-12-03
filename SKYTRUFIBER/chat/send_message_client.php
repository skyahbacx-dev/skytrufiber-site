<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
$message  = trim($_POST["message"] ?? "");

if (!$username)
    exit(json_encode(["status"=>"error", "msg"=>"no username"]));

$stmt = $conn->prepare("
    SELECT id FROM users 
    WHERE email = ? COLLATE utf8mb4_general_ci 
       OR full_name = ? COLLATE utf8mb4_general_ci
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user)
    exit(json_encode(["status"=>"error", "msg"=>"invalid user"]));

$client_id = $user["id"];

// If no text and no media → ignore
if ($message === "")
    exit(json_encode(["status"=>"ok", "msg"=>"empty skipped"]));

$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, 1, 0, NOW())
");
$insert->execute([$client_id, $message]);

echo json_encode(["status"=>"ok"]);
