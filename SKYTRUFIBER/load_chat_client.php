<?php
include "../db_connect.php";

$username = $_GET["client"] ?? null;

$stmt = $pdo->prepare("
    SELECT id FROM users WHERE account_number = ?
");
$stmt->execute([$username]);
$row = $stmt->fetch();

if (!$row) {
    echo json_encode([]);
    exit;
}

$cid = $row["id"];

$pdo->prepare("UPDATE chat SET seen = TRUE WHERE client_id = ? AND sender_type = 'csr'")
    ->execute([$cid]);

$sql = "
    SELECT c.*, 
    COALESCE((
        SELECT json_agg(json_build_object('media_path', m.media_path, 'media_type', m.media_type))
        FROM chat_media m WHERE m.chat_id = c.id
    ), '[]') AS media
    FROM chat c
    WHERE c.client_id = ?
    ORDER BY c.created_at ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$cid]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
