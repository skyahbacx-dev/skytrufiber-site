<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");
require_once "../../db_connect.php";

ini_set("display_errors",1);
error_reporting(E_ALL);

$msgID    = $_POST["id"] ?? 0;
$username = $_POST["username"] ?? null;

if (!$msgID || !$username) {
    echo json_encode(["status"=>"error","msg"=>"Invalid request"]);
    exit;
}

// get user
$stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR full_name=? LIMIT 1");
$stmt->execute([$username,$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status"=>"error","msg"=>"User not found"]);
    exit;
}

$clientID = (int)$user["id"];

// get message
$stmt = $conn->prepare("
    SELECT sender_type, created_at, deleted 
    FROM chat 
    WHERE id=? AND client_id=?
");
$stmt->execute([$msgID,$clientID]);
$msgData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$msgData) {
    echo json_encode(["status"=>"error","msg"=>"Message not found"]);
    exit;
}

if ($msgData["deleted"] == 1) {
    echo json_encode(["status"=>"ok","type"=>"already"]);
    exit;
}

$isClient = ($msgData["sender_type"] === "client");

$ageMin = (time() - strtotime($msgData["created_at"])) / 60;

// Unsend (<10 min)
if ($isClient && $ageMin <= 10) {

    $del = $conn->prepare("
        UPDATE chat 
        SET deleted=1, deleted_at=NOW(), message='', edited=0
        WHERE id=?
    ");
    $del->execute([$msgID]);

    $rm = $conn->prepare("DELETE FROM chat_reactions WHERE chat_id=?");
    $rm->execute([$msgID]);

    echo json_encode(["status"=>"ok","type"=>"unsent"]);
    exit;
}

echo json_encode(["status"=>"ok","type"=>"self-delete"]);
exit;
