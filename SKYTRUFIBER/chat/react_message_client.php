<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$msgID = (int)($_POST["chat_id"] ?? 0);
$emoji = $_POST["emoji"] ?? "";

if (!$msgID || !$emoji) exit("bad");

// Only client reacts here
$userType = "client";

// Postgres UPSERT
$stmt = $conn->prepare("
    INSERT INTO chat_reactions (chat_id, user_type, emoji)
    VALUES (?, ?, ?)
    ON CONFLICT (chat_id, user_type)
    DO UPDATE SET emoji = EXCLUDED.emoji
");
$stmt->execute([$msgID, $userType, $emoji]);

echo "ok";
