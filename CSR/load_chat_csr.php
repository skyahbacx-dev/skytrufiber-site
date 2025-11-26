<?php
require '../db_connect.php';
session_start();

header("Content-Type: application/json");

if (!isset($_SESSION['csr_user'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$client_id = intval($_GET["client_id"] ?? 0);
if ($client_id <= 0) {
    echo json_encode(["messages" => [], "media" => []]);
    exit;
}

// Fetch messages + media
$sql = "
SELECT 
    c.id,
    c.message,
    c.sender_type,
    c.created_at,
    c.seen,
    COALESCE(json_agg(cm.media_path) FILTER (WHERE cm.media_path IS NOT NULL), '[]') AS media
FROM chat c
LEFT JOIN chat_media cm ON cm.chat_id = c.id
WHERE c.client_id = :cid
GROUP BY c.id
ORDER BY c.created_at ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute(["cid" => $client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["messages" => $messages]);
exit;
