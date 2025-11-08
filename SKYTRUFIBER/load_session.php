<?php
include '../db_connect.php';
header('Content-Type: application/json');

try {
    $sql = "
        SELECT 
            s.client_name,
            s.created_at,
            s.archived,
            a.name AS csr
        FROM chat_sessions s
        LEFT JOIN csr_accounts a ON a.id = s.assigned_csr_id
        ORDER BY s.id DESC
    ";
    $stmt = $conn->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows);
} catch (PDOException $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
