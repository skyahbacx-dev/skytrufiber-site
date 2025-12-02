<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");
require_once "../../db_connect.php";

$msgID = $_POST["id"] ?? null;
$emoji = $_POST["emoji"] ?? null;
$username = $_POST["username"] ?? null;

if (!$msgID || !$emoji || !$username) {
    echo json_encode(["status" => "error", "msg" => "Missing data"]);
    exit;
}

// Identify user type
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR full_name = ?");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$userType = $user ? "client" : "csr";

$insert = $conn->prepare("
    INSERT INTO chat_reactions (chat_id, emoji, user_type, created_at)
    VALUES (?, ?, ?, NOW())
");
$insert->execute([$msgID, $emoji, $userType]);

echo json_encode(["status" => "ok"]);
exit;
?>
