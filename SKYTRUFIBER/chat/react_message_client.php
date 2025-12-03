<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

header("Content-Type: application/json");

ini_set("display_errors",1);
error_reporting(E_ALL);

$chat_id = $_POST["chat_id"] ?? null;
$emoji   = $_POST["emoji"] ?? null;

if (!$chat_id || !$emoji) {
    echo json_encode(["status"=>"error","msg"=>"Bad data"]);
    exit;
}

$userType = isset($_SESSION["csr_id"]) ? "csr" : "client";

$stmt = $conn->prepare("
    INSERT INTO chat_reactions (chat_id, emoji, user_type)
    VALUES (?, ?, ?)
");
$stmt->execute([$chat_id, $emoji, $userType]);

echo json_encode(["status"=>"ok"]);
exit;
