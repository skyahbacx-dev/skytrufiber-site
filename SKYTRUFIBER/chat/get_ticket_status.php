<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");

if ($username === "") {
    echo "unresolved";
    exit;
}

// Find user by email or full name
$stmt = $conn->prepare("
    SELECT ticket_status
    FROM users
    WHERE email ILIKE ? 
       OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user not found → default unresolved
if (!$user) {
    echo "unresolved";
    exit;
}

// Return normalized ticket status
$ticket = strtolower($user["ticket_status"] ?? "unresolved");

echo ($ticket === "resolved") ? "resolved" : "unresolved";
