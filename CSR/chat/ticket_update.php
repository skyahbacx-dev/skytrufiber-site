<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$clientID = $_POST["client_id"] ?? null;
$status   = $_POST["status"] ?? null;

if (!$clientID || !$status) {
    echo "Missing data";
    exit;
}

$stmt = $conn->prepare("
    UPDATE users SET ticket_status = :s WHERE id = :id
");
$stmt->execute([
    ":s" => $status,
    ":id" => $clientID
]);

echo "OK";
