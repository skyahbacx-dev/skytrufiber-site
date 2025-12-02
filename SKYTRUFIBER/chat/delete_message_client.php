<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$id = intval($_POST["id"] ?? 0);
$username = $_POST["username"] ?? null;

if (!$id || !$username) exit("bad request");

$stmt = $conn->prepare("
    SELECT id FROM users WHERE email = ? OR full_name = ? LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) exit("no user");

$client_id = (int)$user["id"];

// Update chat message record
$delete = $conn->prepare("
    UPDATE chat SET deleted = TRUE, message = '', deleted_at = NOW()
    WHERE id = ? AND client_id = ?
");
$delete->execute([$id, $client_id]);

// Also remove media
$conn->prepare("DELETE FROM chat_media WHERE chat_id = ?")->execute([$id]);

echo "ok";
?>
