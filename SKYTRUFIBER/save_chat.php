<?php
include '../db_connect.php';
header('Content-Type: application/json');

$sender_type  = $_POST['sender_type'] ?? 'client';
$message      = trim($_POST['message'] ?? '');
$username     = trim($_POST['username'] ?? '');
$client_id    = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
$csr_user     = $_POST['csr_user'] ?? '';
$csr_fullname = $_POST['csr_fullname'] ?? '';

if ($message === '') {
    echo json_encode(['status' => 'error', 'msg' => 'Empty message']);
    exit;
}

/* ===========================================================
   1️⃣ CLIENT MESSAGE HANDLER
   =========================================================== */
if ($sender_type === 'client') {
    // Find or create client
    $stmt = $conn->prepare("SELECT id, assigned_csr FROM clients WHERE name = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $assigned_csr = '';
    $assigned_csr_full = '';

    if (!$row) {
        // Pick a random online CSR
        $csrQ = $conn->query("SELECT username, full_name FROM csr_users WHERE status='active' AND is_online=TRUE ORDER BY RANDOM() LIMIT 1");
        $csr = $csrQ->fetch(PDO::FETCH_ASSOC);

        if ($csr) {
            $assigned_csr = $csr['username'];
            $assigned_csr_full = $csr['full_name'];
        } else {
            $assigned_csr = 'Unassigned';
            $assigned_csr_full = '';
        }

        // Create new client
        $ins = $conn->prepare("INSERT INTO clients (name, assigned_csr, created_at) VALUES (:name, :csr, NOW()) RETURNING id");
        $ins->execute([':name' => $username, ':csr' => $assigned_csr]);
        $client_id = $ins->fetchColumn();
    } else {
        $client_id = $row['id'];
        $assigned_csr = $row['assigned_csr'];

        // Get full name of assigned CSR
        $csrNameQ = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :csr LIMIT 1");
        $csrNameQ->execute([':csr' => $assigned_csr]);
        $csrName = $csrNameQ->fetchColumn();
        $assigned_csr_full = $csrName ?: $assigned_csr;
    }

    // Save client message
    $stmt2 = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, created_at) VALUES (:cid, 'client', :msg, NOW())");
    $stmt2->execute([':cid' => $client_id, ':msg' => $message]);

    // Check if it’s the first chat, send automated greeting
    $existing = $conn->prepare("SELECT COUNT(*) FROM chat WHERE client_id = :cid");
    $existing->execute([':cid' => $client_id]);
    $count = $existing->fetchColumn();

    if ($count <= 2 && $assigned_csr !== 'Unassigned') {
        $greeting = "👋 Hi $username! This is $assigned_csr_full from SkyTruFiber. Thank you for reaching out. How can I assist you today?";
        $stmt3 = $conn->prepare("
            INSERT INTO chat (client_id, sender_type, message, assigned_csr, csr_fullname, created_at)
            VALUES (:cid, 'csr', :msg, :csr, :csr_full, NOW())
        ");
        $stmt3->execute([
            ':cid' => $client_id,
            ':msg' => $greeting,
            ':csr' => $assigned_csr,
            ':csr_full' => $assigned_csr_full
        ]);
    }

    echo json_encode(['status' => 'ok', 'client_id' => $client_id]);
    exit;
}

/* ===========================================================
   2️⃣ CSR MESSAGE HANDLER
   =========================================================== */
if ($sender_type === 'csr' && $client_id > 0) {
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, assigned_csr, csr_fullname, created_at)
        VALUES (:cid, 'csr', :msg, :csr, :csr_full, NOW())
    ");
    $stmt->execute([
        ':cid' => $client_id,
        ':msg' => $message,
        ':csr' => $csr_user,
        ':csr_full' => $csr_fullname
    ]);

    echo json_encode(['status' => 'ok']);
    exit;
}

/* ===========================================================
   3️⃣ INVALID REQUEST
   =========================================================== */
echo json_encode(['status' => 'error', 'msg' => 'Invalid request']);
?>
