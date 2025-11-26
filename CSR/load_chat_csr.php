<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION["csr_user"])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$client_id = $_GET["client_id"] ?? null;

if (!$client_id) {
    echo json_encode([]);
    exit;
}

// Update delivered status for client messages
$pdo->prepare("UPDATE chat SET delivered = TRUE WHERE client_id = :cid AND delivered = FALSE AND sender_type = 'client'")
    ->execute([":cid" => $client_id]);

// Fetch chat + media files in one query
$sql = "
SELECT c.id, c.client_id, c.sender_type, c.message, c.delivered, c.seen, c.created_at, c.user,
       COALESCE(json_agg(json_build_object(
            'id', m.id,
            'media_path', m.media_path,
            'media_type', m.media_type
       ) ORDER BY m.id) FILTER (WHERE m.id IS NOT NULL), '[]') AS media
FROM chat c
LEFT JOIN chat_media m ON m.chat_id = c.id
WHERE c.client_id = :cid
GROUP BY c.id
ORDER BY c.id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":cid" => $client_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
?>
