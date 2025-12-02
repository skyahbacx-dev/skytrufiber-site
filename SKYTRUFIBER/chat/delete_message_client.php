<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$msgID    = $_POST["id"] ?? 0;
$username = $_POST["username"] ?? null;

if (!$msgID || !$username) {
    echo json_encode(["status" => "error", "msg" => "Invalid request"]);
    exit;
}

// Get client account
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR full_name = ? LIMIT 1");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "msg" => "User not found"]);
    exit;
}

$clientID = (int)$user["id"];

// Get message info
$stmt = $conn->prepare("
    SELECT sender_type, created_at, deleted
    FROM chat
    WHERE id = ? AND client_id = ?
    LIMIT 1
");
$stmt->execute([$msgID, $clientID]);
$msgData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$msgData) {
    echo json_encode(["status" => "error", "msg" => "Message not found"]);
    exit;
}

// Already deleted? prevent repeated action on UI
if ($msgData["deleted"] == 1) {
    echo json_encode(["status" => "ok", "type" => "already-deleted"]);
    exit;
}

$isClientSender = ($msgData["sender_type"] === "client");
$messageAgeMinutes = (time() - strtotime($msgData["created_at"])) / 60;

// ===== UNSEND RULE (10 min limit, sender only) =====
if ($isClientSender && $messageAgeMinutes <= 10) {

    // Mark soft delete + remove content + reactions
    $update = $conn->prepare("
        UPDATE chat
        SET deleted = 1, deleted_at = NOW(), message = '', edited = 0
        WHERE id = ?
    ");
    $update->execute([$msgID]);

    // Remove reactions from others
    $removeReacts = $conn->prepare("DELETE FROM chat_reactions WHERE chat_id = ?");
    $removeReacts->execute([$msgID]);

    echo json_encode(["status" => "ok", "type" => "unsent"]);
    exit;
}

// ===== SELF DELETE (client hides but CSR still sees original) =====
echo json_encode(["status" => "ok", "type" => "self-delete"]);
exit;
?>
