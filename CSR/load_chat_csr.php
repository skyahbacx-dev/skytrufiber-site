<?php
include '../db_connect.php';
header('Content-Type: application/json');

date_default_timezone_set("Asia/Manila");

$client_id = $_GET['client_id'] ?? 0;
$client_id = (int)$client_id;

if (!$client_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        ch.message,
        ch.sender_type,
        ch.media_path,
        ch.media_type,
        c.name AS client_name,
        DATE_FORMAT(CONVERT_TZ(ch.created_at,'+00:00','+08:00'),'%b %d %l:%i %p') AS created_at
    FROM chat ch
    JOIN clients c ON ch.client_id = c.id
    WHERE ch.client_id = :cid
    ORDER BY ch.id ASC
");
$stmt->execute([':cid'=>$client_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
