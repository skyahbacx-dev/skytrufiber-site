<?php
require_once "../../db_connect.php";

$client_id   = $_POST["client_id"] ?? null;
$message     = $_POST["message"] ?? "";
$sender_type = $_POST["sender_type"] ?? "CLIENT";

if (!$client_id || trim($message) === "") exit;

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen)
    VALUES (?, ?, ?, TRUE, FALSE)
");
$stmt->execute([$client_id, $sender_type, $message]);
