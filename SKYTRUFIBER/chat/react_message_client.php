<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$msgID = (int)($_POST["chat_id"] ?? 0);
$emoji = $_POST["emoji"] ?? "";

if (!$msgID || !$emoji) exit("bad");

// Always client in this panel
$userType = "client";

// Upsert reaction (SQLite & MySQL compatible)
$stmt = $conn->prepare("
    INSERT INTO chat_reactions (chat_id, emoji, user_type)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE emoji = VALUES(emoji)
");
$stmt->execute([$msgID, $emoji, $userType]);

echo "ok";
