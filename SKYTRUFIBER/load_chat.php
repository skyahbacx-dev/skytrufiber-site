<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

$client_id = 0;
$viewer    = $_GET['viewer'] ?? ''; // 'csr' or 'client'

if (isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
} elseif (isset($_GET['client'])) {
    $stmt = $conn->prepare("SELECT id FROM clients WHERE name = :name LIMIT 1");
    $stmt->execute([':name' => $_GET['client']]);
    $client_id = (int)$stmt->fetchColumn();
}

if (!$client_id) {
    echo json_encode([]);
    exit;
}

/* Mark messages as seen depending on viewer */
if ($viewer === 'csr') {
    // CSR viewing: mark client messages as seen
    $up = $conn->prepare("UPDATE chat SET is_seen = TRUE WHERE client_id = :cid AND sender_type = 'client'");
    $up->execute([':cid' => $client_id]);
} elseif (isset($_GET['client'])) {
    // Client viewing: mark CSR messages as seen
    $up = $conn->prepare("UPDATE chat SET is_seen = TRUE WHERE client_id = :cid AND sender_type = 'csr'");
    $up->execute([':cid' => $client_id]);
}

/* Fetch chat history */
$stmt = $conn->prepare("
    SELECT
        ch.message,
        ch.sender_type,
        ch.created_at,
        ch.assigned_csr,
        ch.csr_fullname,
        ch.file_path,
        ch.file_name,
        ch.is_seen,
        c.name AS client_name
    FROM chat ch
    JOIN clients c ON ch.client_id = c.id
    WHERE ch.client_id = :cid
    ORDER BY ch.created_at ASC
");
$stmt->execute([':cid' => $client_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = [];
foreach ($rows as $row) {
    $out[] = [
        'message'      => $row['message'],
        'sender_type'  => $row['sender_type'],
        'created_at'   => $row['created_at'],
        'client_name'  => $row['client_name'],
        'assigned_csr' => $row['assigned_csr'],
        'csr_fullname' => $row['csr_fullname'],
        'file_path'    => $row['file_path'],
        'file_name'    => $row['file_name'],
        'is_seen'      => $row['is_seen'],
    ];
}

echo json_encode($out);
