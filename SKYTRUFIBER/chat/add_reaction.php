<?php
require_once "../../db_connect.php";
if (!isset($_SESSION)) session_start();

$msgID  = $_POST["id"] ?? 0;
$emoji  = $_POST["emoji"] ?? null;
$username = $_POST["username"] ?? null;

if (!$msgID || !$emoji) exit("bad");

// Determine identity as client
$user_type = "client";

$stmt = $conn->prepare("
    INSERT INTO chat_reactions (chat_id, emoji, user_type)
    VALUES (?, ?, ?)
");
$stmt->execute([$msgID, $emoji, $user_type]);

echo "ok";
