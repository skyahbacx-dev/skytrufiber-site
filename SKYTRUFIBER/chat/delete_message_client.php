<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$msgID = (int)($_POST["id"] ?? 0);
$username = trim($_POST["username"] ?? "");

if (!$msgID || !$username)
    exit(json_encode(["status"=>"error","msg"=>"invalid"]));

// Find user
$stmt = $conn->prepare("
    SELECT id FROM users
    WHERE email ILIKE ?
       OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user)
    exit(json_encode(["status"=>"error","msg"=>"no user"]));

$client_id = $user["id"];

// Fetch message
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

if ($msg["deleted"])
    exit(json_encode(["status"=>"ok","type"=>"already-deleted"]));

$isClient = ($msg["sender_type"] === "client");
$age = (time() - strtotime($msg["created_at"])) / 60;

// UNSEND (<10 min)
if ($isClient && $age <= 10) {

    $del = $conn->prepare("
        UPDATE chat
        SET deleted = TRUE, deleted_at = NOW(), message = '', edited = FALSE
        WHERE id = ?
    ");
    $del->execute([$msgID]);

    $rm = $conn->prepare("DELETE FROM chat_reactions WHERE chat_id = ?");
    $rm->execute([$msgID]);

    exit(json_encode(["status"=>"ok","type"=>"unsent"]));
}

// SELF-DELETE ONLY
exit(json_encode(["status"=>"ok","type"=>"self-delete"]));
