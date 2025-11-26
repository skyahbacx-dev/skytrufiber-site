<?php
require '../db_connect.php';
session_start();

if (!isset($_SESSION['csr_user'])) {
    http_response_code(401);
    exit;
}

header('Content-Type: application/json');

// Fetch unassigned or assigned to logged CSR user
$csr_username = $_SESSION["csr_user"];

$query = $pdo->prepare("
    SELECT 
        u.id,
        u.full_name,
        u.barangay,
        u.district,
        u.assigned_csr,
        u.is_online,
        MAX(c.created_at) AS last_message_at
    FROM users u
    LEFT JOIN chat c ON c.client_id = u.id
    GROUP BY u.id
    ORDER BY last_message_at DESC NULLS LAST
");

$query->execute();
$users = $query->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["clients" => $users]);
exit;
