<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$msgID = (int)($_POST["chat_id"] ?? 0);
$emoji = trim($_POST["emoji"] ?? "");

if (!$msgID || !$emoji)
    exit("bad");

// Always client in client panel
$userType = "client";

$stmt = $conn->prepare("
    INSERT INTO chat_reactions (chat_id, emoji, user_type)
    VALUES ($1, $2, $3)
    ON CONFLICT (chat_id, user_type)
    DO UPDATE SET emoji = EXCLUDED.emoji
");
$stmt->execute([$msgID, $emoji, $userType]);

echo "ok";
