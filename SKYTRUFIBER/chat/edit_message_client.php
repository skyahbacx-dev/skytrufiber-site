<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$id  = (int)($_POST["id"] ?? 0);
$msg = trim($_POST["message"] ?? "");

if ($id <= 0)
    exit(json_encode(["status"=>"error", "msg"=>"invalid id"]));

if ($msg === "")
    exit(json_encode(["status"=>"error", "msg"=>"empty message"]));

$stmt = $conn->prepare("
    UPDATE chat
    SET message = $1, edited = TRUE, updated_at = NOW()
    WHERE id = $2
      AND sender_type = 'client'
      AND deleted = FALSE
");
$stmt->execute([$msg, $id]);

echo json_encode(["status"=>"success"]);
