<?php
include "../db_connect.php";
session_start();

$client_id = $_GET["client_id"] ?? 0;
$csr       = $_SESSION["csr_user"] ?? "";

$sql = "
SELECT 
    c.id,
    c.message,
    c.sender_type,
    c.created_at,
    COALESCE(
        json_agg(
            json_build_object('media_path', m.media_path, 'media_type', m.media_type)
        ) FILTER (WHERE m.id IS NOT NULL),
        '[]'
    ) AS media
FROM chat c
LEFT JOIN chat_media m ON m.chat_id = c.id
WHERE c.client_id = :id
GROUP BY c.id
ORDER BY c.created_at ASC;
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":id" => $client_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>
