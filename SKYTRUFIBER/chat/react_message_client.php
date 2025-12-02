<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$chat_id = $_POST["chat_id"] ?? null;
$emoji   = $_POST["emoji"] ?? null;

if (!$chat_id || !$emoji) exit("Invalid data");

// Determine sender type
$sender = isset($_SESSION["csr_id"]) ? "csr" : "client";

// Insert or update reaction (toggle support)
$stmt = $conn->prepare("
    INSERT INTO chat_reactions (chat_id, emoji, user_type)
    VALUES (?, ?, ?)
    ON CONFLICT (chat_id, emoji, user_type) DO UPDATE SET
        emoji = EXCLUDED.emoji
");
$stmt->execute([$chat_id, $emoji, $sender]);

echo "ok";
?>
