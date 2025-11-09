<?php
include '../db_connect.php';
header('Content-Type: application/json');

$username = trim($_GET['username'] ?? '');
if ($username === '') {
    echo json_encode(['status' => 'error', 'msg' => 'No username provided']);
    exit;
}

try {
    // Step 1: Find or create client record
    $stmt = $conn->prepare("SELECT id, assigned_csr FROM clients WHERE name = :name LIMIT 1");
    $stmt->execute([':name' => $username]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        // Create new client placeholder
        $conn->prepare("INSERT INTO clients (name, assigned_csr, created_at) VALUES (:n, 'Unassigned', NOW())")
             ->execute([':n' => $username]);
        $client_id = $conn->lastInsertId();
        $assigned_csr = 'Unassigned';
    } else {
        $client_id = $client['id'];
        $assigned_csr = $client['assigned_csr'];
    }

    // Step 2: Find a random online CSR (PostgreSQL uses RANDOM())
    $csrStmt = $conn->query("SELECT username, full_name FROM csr_users WHERE is_online = TRUE ORDER BY RANDOM() LIMIT 1");
    $csr = $csrStmt->fetch(PDO::FETCH_ASSOC);

    if ($csr) {
        $csr_user = $csr['username'];
        $csr_name = $csr['full_name'];
        $message = "👋 Hi $username! This is $csr_name from SkyTruFiber. How can I assist you today?";

        // Step 3: Assign the CSR to the client
        $update = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :cid");
        $update->execute([':csr' => $csr_user, ':cid' => $client_id]);

        // Step 4: Save greeting message to chat
        $stmt = $conn->prepare("
            INSERT INTO chat (client_id, sender_type, message, assigned_csr, csr_fullname, created_at)
            VALUES (:cid, 'csr', :msg, :csr, :csr_full, NOW())
        ");
        $stmt->execute([
            ':cid' => $client_id,
            ':msg' => $message,
            ':csr' => $csr_user,
            ':csr_full' => $csr_name
        ]);

        echo json_encode([
            'status' => 'success',
            'csr' => $csr_name,
            'message' => $message
        ]);
    } else {
        // No online CSR
        $msg = "👋 Hi $username! All our CSRs are currently offline, but we’ll get back to you soon.";
        $stmt = $conn->prepare("
            INSERT INTO chat (client_id, sender_type, message, created_at)
            VALUES (:cid, 'system', :msg, NOW())
        ");
        $stmt->execute([':cid' => $client_id, ':msg' => $msg]);

        echo json_encode([
            'status' => 'no_csr',
            'message' => $msg
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>
