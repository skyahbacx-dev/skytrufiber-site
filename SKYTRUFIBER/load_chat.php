<?php
include '../db_connect.php';
header('Content-Type: application/json');

date_default_timezone_set("Asia/Manila");

$client_id = 0;

if (!empty($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
} elseif (!empty($_GET['client'])) {
    $stmt = $conn->prepare("SELECT id FROM clients WHERE LOWER(name)=LOWER(:name) LIMIT 1");
    $stmt->execute([":name"=>$_GET['client']]);
    $client_id = $stmt->fetchColumn();
}

if (!$client_id) {
    echo json_encode([]);
    exit;
}

$query = $conn->prepare("
    SELECT message, sender_type,
           DATE_FORMAT(CONVERT_TZ(created_at,'+00:00','+08:00'), '%b %d %l:%i %p') AS created_at
    FROM chat
    WHERE client_id = :cid
    ORDER BY id ASC
");
$query->execute([':cid'=>$client_id]);

echo json_encode($query->fetchAll(PDO::FETCH_ASSOC));
?>
