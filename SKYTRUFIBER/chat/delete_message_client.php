<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$msgID    = (int)($_POST["id"] ?? 0);
$username = trim($_POST["username"] ?? "");

if (!$msgID || !$username)
    exit(json_encode(["status"=>"error","msg"=>"invalid"]));

// Find user
$stmt = $conn->prepare("
    SELECT id FROM users
    WHERE email = $1 OR full_name = $1
    LIMIT 1
");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user)
    exit(json_encode(["status"=>"error","msg"=>"unknown user"]));

$client_id = (int)$user["id"];

// Fetch message
$stmt = $conn->prepare("
    SELECT sender_type, created_at, deleted
    FROM chat
    WHERE id = $1 AND client_id = $2
    LIMIT 1
");
$stmt->execute([$msgID, $client_id]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$msg)
    exit(json_encode(["status"=>"error","msg"=>"missing msg"]));

if ($msg["deleted"] === true)
    exit(json_encode(["status"=>"ok","type"=>"already-deleted"]));

$isClientSender = ($msg["sender_type"] === "client");
$ageMinutes = (time() - strtotime($msg["created_at"])) / 60;

// UN SEND (<10 mins)
if ($isClientSender && $ageMinutes <= 10) {

    $update = $conn->prepare("
        UPDATE chat
        SET deleted = TRUE,
            deleted_at = NOW(),
            message = '',
            edited = FALSE
        WHERE id = $1
    ");
    $update->execute([$msgID]);

    // Remove reactions
    $rm = $conn->prepare("DELETE FROM chat_reactions WHERE chat_id = $1");
    $rm->execute([$msgID]);

    exit(json_encode(["status"=>"ok","type"=>"unsent"]));
}

// SELF DELETE (client hides only)
exit(json_encode(["status"=>"ok","type"=>"self-delete"]));
