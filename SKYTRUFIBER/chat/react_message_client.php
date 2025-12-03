<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$msgID = (int)($_POST["chat_id"] ?? 0);
$emoji = trim($_POST["emoji"] ?? "");

// Validate
if ($msgID <= 0 || $emoji === "") {
    echo json_encode(["status" => "error", "msg" => "invalid input"]);
    exit;
}

// Always client in this panel
$userType = "client";

/*
-----------------------------------------------
 POSTGRESQL COMPATIBLE UPSERT
 chat_id + user_type must be UNIQUE in DB:
   ALTER TABLE chat_reactions ADD CONSTRAINT chat_react_unique
   UNIQUE (chat_id, user_type);
-----------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO chat_reactions (chat_id, emoji, user_type)
    VALUES (?, ?, ?)
    ON CONFLICT (chat_id, user_type)
    DO UPDATE SET emoji = EXCLUDED.emoji
");
$stmt->execute([$msgID, $emoji, $userType]);

echo json_encode(["status" => "ok"]);
