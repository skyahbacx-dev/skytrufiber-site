<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

date_default_timezone_set("Asia/Manila");

$client_id = 0;

// Accept client_id or client username
if (isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
} elseif (isset($_GET['client'])) {
    $stmt = $conn->prepare("SELECT id FROM clients WHERE fullname = :name LIMIT 1");
    $stmt->execute([":name" => $_GET['client']]);
    $client_id = $stmt->fetchColumn();
}

if (!$client_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        ch.message,
        ch.sender_type,
        CONVERT_TZ(ch.created_at, '+00:00', '+08:00') AS created_at,
        ch.media_path,
        ch.media_type,
        ch.csr_fullname,
        c.fullname AS client_name
    FROM chat ch
    JOIN clients c ON ch.client_id = c.id
    WHERE ch.client_id = :cid
    ORDER BY ch.id ASC
");
$stmt->execute([':cid'=>$client_id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
?>
