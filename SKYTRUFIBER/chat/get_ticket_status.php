<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

// Username passed from chat_support.js
$username = $_POST["username"] ?? null;

if (!$username) {
    echo "unresolved";
    exit;
}

// Get the client's database record
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

// Return “resolved” or “unresolved”
echo $row["ticket_status"] === "resolved" ? "resolved" : "unresolved";
