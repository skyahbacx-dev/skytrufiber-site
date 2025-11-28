<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;

if (!$client_id) {
    echo "Missing client ID";
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT c.id, c.sender_type, c.message, c.created_at,
               m.media_path, m.media_type
        FROM chat c
        LEFT JOIN chat_media m ON m.chat_id = c.id
        WHERE c.client_id = ?
        ORDER BY c.created_at ASC
    ");

    $stmt->execute([$client_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($messages);

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
