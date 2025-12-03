<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$id = (int)($_POST["id"] ?? 0);
$msg = trim($_POST["message"] ?? "");

if ($id <= 0)
    exit(json_encode(["status"=>"error", "msg"=>"invalid id"]));

if ($msg === "")
    exit(json_encode(["status"=>"error", "msg"=>"empty"]));

$stmt = $conn->prepare("
    UPDATE chat
    SET message = ?, edited = 1, updated_at = NOW()
    WHERE id = ? AND sender_type = 'client' AND deleted = 0
");
$stmt->execute([$msg, $id]);

echo json_encode(["status"=>"success"]);
