<?php
include '../db_connect.php';
header('Content-Type: application/json');

// Get client name
$client_name = $_GET['username'] ?? '';
if (!$client_name) {
    echo json_encode(['status' => 'error', 'msg' => 'No username']);
    exit;
}

try {
    // Step 1: Delete previous chat messages for this client
    $stmt = $conn->prepare("DELETE FROM chat_messages WHERE client_name = :client_name");
    $stmt->execute([':client_name' => $client_name]);

    // Step 2: Find an online CSR randomly
    $csrStmt = $conn->query("
        SELECT id, name 
        FROM csr_accounts 
        WHERE is_online = TRUE 
        ORDER BY RANDOM() 
        LIMIT 1
    ");
    $csrData = $csrStmt->fetch(PDO::FETCH_ASSOC);

    if ($csrData) {
        $csr_id = $csrData['id'];
        $csr_name = $csrData['name'];

        // Step 3: Create new chat session
        $insert = $conn->prepare("
            INSERT INTO chat_sessions (client_name, assigned_csr_id, created_at)
            VALUES (:client_name, :csr_id, NOW())
        ");
        $insert->execute([
            ':client_name' => $client_name,
            ':csr_id' => $csr_id
        ]);

        // Step 4: Send CSR automated welcome message
        $welcome = "👋 Hi, this is CSR $csr_name. How can I assist you today?";
        $msg = $conn->prepare("
            INSERT INTO chat_messages (client_name, sender_type, assigned_csr_id, message, created_at)
            VALUES (:client_name, 'csr', :csr_id, :message, NOW())
        ");
        $msg->execute([
            ':client_name' => $client_name,
            ':csr_id' => $csr_id,
            ':message' => $welcome
        ]);

        echo json_encode(['status' => 'success', 'csr' => $csr_name]);
    } else {
        echo json_encode(['status' => 'no_csr', 'msg' => 'No online CSR found']);
    }

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
