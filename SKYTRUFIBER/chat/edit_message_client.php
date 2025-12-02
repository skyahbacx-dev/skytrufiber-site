<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$id      = $_POST["id"] ?? null;
$message = trim($_POST["message"] ?? "");

if (!$id) exit(json_encode(["status" => "error", "msg" => "Invalid ID"]));

// Prevent saving blank messages
if ($message === "") {
    exit(json_encode(["status" => "error", "msg" => "Message cannot be empty"]));
}

// Update message text + mark as edited
$stmt = $conn->prepare("
    UPDATE chat
    SET message = ?, edited = 1, updated_at = NOW()
    WHERE id = ? AND sender_type = 'client' AND deleted = 0
");
$stmt->execute([$message, $id]);

echo json_encode(["status" => "success", "msg" => "Message updated"]);
?>
