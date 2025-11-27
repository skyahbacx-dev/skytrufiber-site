<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$user      = $_POST["user"] ?? null;
$typing    = $_POST["typing"] ?? null;

if (!$client_id || $typing === null || !$user) {
    echo "Missing data";
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO typing_status (client_id, typing, updated_at, user)
        VALUES (:client_id, :typing, NOW(), :user)
        ON CONFLICT (client_id)
        DO UPDATE SET typing = :typing, updated_at = NOW(), user = :user
    ");
    $stmt->execute([
        ":client_id" => $client_id,
        ":typing"    => $typing,
        ":user"      => $user,
    ]);

    echo "OK";

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
