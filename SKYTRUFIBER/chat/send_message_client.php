<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
$message  = trim($_POST["message"] ?? "");

if (!$username)
    exit(json_encode(["status"=>"error", "msg"=>"no username"]));

// Find user (PostgreSQL — NO COLLATE)
$stmt = $conn->prepare("
    SELECT id FROM users 
    WHERE email = $1 
       OR full_name = $1
    LIMIT 1
");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user)
    exit(json_encode(["status"=>"error", "msg"=>"invalid user"]));

$client_id = (int)$user["id"];

// Ignore empty text-only messages
if ($message === "")
    exit(json_encode(["status"=>"ok", "msg"=>"empty skipped"]));

$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES ($1, 'client', $2, TRUE, FALSE, NOW())
");
$insert->execute([$client_id, $message]);

echo json_encode(["status"=>"ok"]);
