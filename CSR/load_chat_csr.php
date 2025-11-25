<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$csr_user  = $_SESSION["csr_user"] ?? null;
$client_id = (int)($_GET["client_id"] ?? 0);

if (!$csr_user || !$client_id) { echo "[]"; exit; }

$sql = "
    SELECT c.id AS chat_id, c.sender_type, c.message, c.created_at, c.seen, c.delivered,
           m.media_path, m.media_type
    FROM chat c
    LEFT JOIN chat_media m ON c.id = m.chat_id
    WHERE c.client_id = :cid
    ORDER BY c.created_at ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([":cid" => $client_id]);

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$conn->prepare("
    UPDATE chat SET seen = true
    WHERE client_id = :cid AND sender_type = 'client' AND seen = false
")->execute([":cid" => $client_id]);

echo json_encode($messages);
exit;
?>
