<?php
include '../db_connect.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$client_name = trim($_POST['client_name'] ?? '');
$csr_user = trim($_POST['csr_user'] ?? '');

try {
    if ($action === 'typing_start') {
        $stmt = $conn->prepare("
            UPDATE clients
            SET typing_csr = :csr_user,
                typing_time = NOW()
            WHERE name = :client_name
        ");
        $stmt->execute([
            ':csr_user' => $csr_user,
            ':client_name' => $client_name
        ]);
        echo json_encode(['status' => 'ok']);
        exit;
    }

    if ($action === 'typing_stop') {
        $stmt = $conn->prepare("
            UPDATE clients
            SET typing_csr = NULL
            WHERE name = :client_name
        ");
        $stmt->execute([':client_name' => $client_name]);
        echo json_encode(['status' => 'ok']);
        exit;
    }

    if ($action === 'check') {
        $stmt = $conn->prepare("
            SELECT typing_csr, typing_time
            FROM clients
            WHERE name = :client_name
        ");
        $stmt->execute([':client_name' => $client_name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['typing_csr'])) {
            echo json_encode([
                'typing' => true,
                'csr' => $row['typing_csr']
            ]);
        } else {
            echo json_encode(['typing' => false]);
        }
        exit;
    }

    echo json_encode(['status' => 'none']);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
