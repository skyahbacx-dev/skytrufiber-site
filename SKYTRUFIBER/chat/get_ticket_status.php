<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;

if (!$username) {
    echo "unresolved";
    exit;
}

// Get ticket status directly from users table
$stmt = $conn->prepare("
    SELECT ticket_status
    FROM users 
    WHERE username = :u
    LIMIT 1
");
$stmt->execute([":u" => $username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "unresolved";
    exit;
}

// Return correct status
if ($row["ticket_status"] === "resolved") {
    echo "resolved";
} else {
    echo "unresolved";
}
