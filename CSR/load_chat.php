<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

$client_id = 0;

// ✅ Accept client_id OR username
if (isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
} elseif (isset($_GET['client'])) {
    $stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
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
        ch.created_at,
        ch.assigned_csr,
        ch.csr_fullname,
        c.name AS client_name
    FROM chat ch
    JOIN clients c ON ch.client_id = c.id
    WHERE ch.client_id = :cid
    ORDER BY ch.created_at ASC
");
$stmt->execute([':cid' => $client_id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$messages = [];

foreach ($rows as $row) {
    $messages[] = [
        'message'      => $row['message'],
        'sender_type'  => $row['sender_type'],
        'created_at'   => $row['created_at'],
        'client_name'  => $row['client_name'],
        'assigned_csr' => $row['assigned_csr'],
        'csr_fullname' => $row['csr_fullname'],
    ];
}

echo json_encode($messages);
?>
