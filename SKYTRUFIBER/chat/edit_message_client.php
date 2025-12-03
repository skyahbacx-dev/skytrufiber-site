<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

header("Content-Type: application/json");
ini_set("display_errors",1);
error_reporting(E_ALL);

$id      = $_POST["id"] ?? null;
$message = trim($_POST["message"] ?? "");

if (!$id) {
    echo json_encode(["status"=>"error","msg"=>"Invalid ID"]);
    exit;
}

if ($message === "") {
    echo json_encode(["status"=>"error","msg"=>"Message cannot be empty"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE chat
    SET message = ?, edited = 1, updated_at = NOW()
    WHERE id = ? AND sender_type='client' AND deleted=0
");
$stmt->execute([$message, $id]);

echo json_encode(["status"=>"ok","msg"=>"Updated"]);
