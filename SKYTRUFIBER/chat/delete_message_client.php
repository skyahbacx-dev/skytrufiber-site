<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$msgID = (int)($_POST["id"] ?? 0);
$username = trim($_POST["username"] ?? "");

if (!$msgID || !$username)
    exit(json_encode(["status"=>"error","msg"=>"invalid"]));

// --- USER LOOKUP (PostgreSQL SAFE) ---
$stmt = $conn->prepare("
    SELECT id 
    FROM users
    WHERE email ILIKE ?
       OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user)
    exit(json_encode(["status"=>"error","msg"=>"no user"]));

$client_id = (int)$user["id"];

// FETCH MESSAGE
$stmt = $conn->prepare("
    SELECT sender_type, created_at, deleted
    FROM chat
    WHERE id = ? AND client_id = ?
    LIMIT 1
");
$stmt->execute([$msgID, $client_id]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$msg)
    exit(json_encode(["status"=>"error","msg"=>"missing msg"]));

if ($msg["deleted"] == 1)
    exit(json_encode(["status"=>"ok","type"=>"already-deleted"]));

$isClientSender = ($msg["sender_type"] === "client");
$age = (time() - strtotime($msg["created_at"])) / 60;

// UNSEND RULE (<10 minutes)
if ($isClientSender && $age <= 10) {

    // Mark message as deleted
    $update = $conn->prepare("
        UPDATE chat
        SET deleted = TRUE, deleted_at = NOW(), message = '', edited = FALSE
        WHERE id = ?
    ");
    $update->execute([$msgID]);

    // Remove reactions
    $rm = $conn->prepare("DELETE FROM chat_reactions WHERE chat_id = ?");
    $rm->execute([$msgID]);

    exit(json_encode(["status"=>"ok","type"=>"unsent"]));
}

// CLIENT SELF-DELETE (HIDE ONLY, NOT REMOVE)
exit(json_encode(["status"=>"ok","type"=>"self-delete"]));
