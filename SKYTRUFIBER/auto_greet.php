<?php
include '../db_connect.php';
header('Content-Type: application/json');

$username = $_GET['username'] ?? '';
if (!$username) {
    echo json_encode(['status' => 'error', 'msg' => 'No username']);
    exit;
}

try {
    // Find random online CSR (PostgreSQL uses RANDOM())
    $csrStmt = $conn->query("SELECT id, name FROM csr_accounts WHERE is_online = TRUE ORDER BY RANDOM() LIMIT 1");
    $csr = $csrStmt->fetch(PDO::FETCH_ASSOC);

    if ($csr) {
        $csr_id = $csr['id'];
        $csr_name = $csr['name'];
        $message = "👋 Hi $username! This is CSR $csr_name. How can I assist you today?";

        // Save greeting in chat_messages
        $stmt = $conn->prepare("
            INSERT INTO chat_messages (client_name, sender_type, assigned_csr_id, message, created_at)
            VALUES (:client_name, 'csr', :csr_id, :message, NOW())
        ");
        $stmt->execute([
            ':client_name' => $username,
            ':csr_id' => $csr_id,
            ':message' => $message
        ]);

        echo json_encode([
            'status' => 'success',
            'csr' => $csr_name,
            'message' => $message
        ]);
    } else {
        echo json_encode([
            'status' => 'no_csr',
            'message' => "👋 Hi $username! All CSRs are currently offline, but we’ll get back to you soon."
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>
