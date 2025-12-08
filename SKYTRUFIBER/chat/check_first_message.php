<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
if ($username === "") exit("error");

// Find client ID
$stmt = $conn->prepare("
    SELECT id 
    FROM users 
    WHERE email ILIKE ? OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit("no-user");
}

$client_id = (int)$user["id"];

// Check if this client has ANY messages (sent or received)
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM chat
    WHERE client_id = ?
");
$stmt->execute([$client_id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

// If no messages at all → first time → trigger greeting
if ((int)$row["total"] === 0) {
    echo "empty";
} else {
    echo "has-messages";
}
