<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");

if (!$username) exit("NO_USER");

// Find client
$stmt = $conn->prepare("
    SELECT id FROM users
    WHERE email ILIKE ? OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) exit("NO_CLIENT");

$client_id = $client["id"];

// Count messages
$m = $conn->prepare("SELECT COUNT(*) FROM chat WHERE client_id = ?");
$m->execute([$client_id]);
$count = (int)$m->fetchColumn();

// If already has a conversation → no greeting
if ($count > 0) exit("NO_GREETING");

// Determine greeting
$hour = (int)date("H");
if ($hour < 12) {
    $greet = "Good morning! How may we assist you today?";
} elseif ($hour < 18) {
    $greet = "Good afternoon! How may we assist you today?";
} else {
    $greet = "Good evening! How may we assist you today?";
}

// Insert greeting as CSR message
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'csr', ?, TRUE, FALSE, NOW())
");
$insert->execute([$client_id, $greet]);

echo "GREETED";
?>
