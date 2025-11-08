<?php
include '../db_connect.php';
header('Content-Type: application/json');

$username = $_GET['username'] ?? '';
if (!$username) {
    echo json_encode(['status' => 'error', 'msg' => 'No username provided']);
    exit;
}

try {
    // Archive previous sessions for this client safely using prepared statement
    $stmt = $conn->prepare("UPDATE chat_sessions SET archived = TRUE WHERE client_name = :username");
    $stmt->execute([':username' => $username]);

    echo json_encode(['status' => 'archived']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>
