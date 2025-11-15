<?php
include '../db_connect.php';
header('Content-Type: application/json');

date_default_timezone_set("Asia/Manila");

$username = $_GET['username'] ?? null;
$client_id = 0;

if ($username) {
    $stmt = $conn->prepare("SELECT id FROM clients WHERE LOWER(name)=LOWER(:n) LIMIT 1");
    $stmt->execute([":n"=>$username]);
    $client_id = $stmt->fetchColumn();
}

if (!$client_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        message,
        sender_type,
        media_path,
        media_type,
        DATE_FORMAT(CONVERT_TZ(created_at,'+00:00','+08:00'),'%b %d %l:%i %p') AS created_at
    FROM chat
    WHERE client_id = :cid
    ORDER BY id ASC
");
$stmt->execute([':cid'=>$client_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
