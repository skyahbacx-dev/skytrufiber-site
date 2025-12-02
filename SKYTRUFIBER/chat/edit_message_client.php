<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$id      = $_POST["id"] ?? null;
$message = $_POST["message"] ?? null;

if (!$id || $message === null) exit("invalid");

$stmt = $conn->prepare("
    UPDATE chat
    SET message = ?, updated_at = NOW()
    WHERE id = ? AND sender_type = 'client'
");
$stmt->execute([$message, $id]);

echo "OK";
