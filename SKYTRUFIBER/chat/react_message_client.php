<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$msgID = (int)($_POST["chat_id"] ?? 0);
$emoji = $_POST["emoji"] ?? "";

if (!$msgID || !$emoji) exit("bad");

$userType = "client";

// Remove old reaction from same user type (simple & universal)
$delete = $conn->prepare("
    DELETE FROM chat_reactions 
    WHERE chat_id = ? AND user_type = ?
");
$delete->execute([$msgID, $userType]);

// Insert new reaction
$insert = $conn->prepare("
    INSERT INTO chat_reactions (chat_id, emoji, user_type)
    VALUES (?, ?, ?)
");
$insert->execute([$msgID, $emoji, $userType]);

echo "ok";
?>
