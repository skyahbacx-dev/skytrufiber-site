<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$msgID = $_POST["id"] ?? 0;
$username = $_POST["username"] ?? null;

if (!$msgID || !$username) {
    echo json_encode(["status" => "error", "msg" => "Bad request"]);
    exit;
}

// Get user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR full_name = ? LIMIT 1");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) exit(json_encode(["status" => "error", "msg" => "User not found"]));

$clientID = (int)$user["id"];

// Get message details
$msg = $conn->prepare("SELECT sender_type, created_at FROM chat WHERE id = ? AND client_id = ?");
$msg->execute([$msgID, $clientID]);
$row = $msg->fetch(PDO::FETCH_ASSOC);

if (!$row) exit(json_encode(["status" => "error", "msg" => "Message not found"]));

$isClient = ($row["sender_type"] == "client");
$messageAgeMinutes = (time() - strtotime($row["created_at"])) / 60;

// UNSEND RULES
if ($isClient && $messageAgeMinutes < 10) {
    // Remove for both
    $update = $conn->prepare("UPDATE chat SET deleted = TRUE, deleted_at = NOW(), message = '' WHERE id = ?");
    $update->execute([$msgID]);

    echo json_encode(["status" => "ok", "type" => "unsent"]);
    exit;
}

// Delete only for client UI (CSR still sees original)
echo json_encode(["status" => "ok", "type" => "self-delete"]);
exit;
?>
