<?php
include '../db_connect.php';
header('Content-Type: application/json');

$username = trim($_GET['username'] ?? '');

if ($username === '') {
    echo json_encode([
        'status' => 'error',
        'msg' => 'No username provided'
    ]);
    exit;
}

try {

    /* ============================================================
       STEP 1: Check if Client Exists — If not, create the client
    ============================================================ */
    $stmt = $conn->prepare("
        SELECT id, assigned_csr 
        FROM clients 
        WHERE name = :name 
        LIMIT 1
    ");
    $stmt->execute([':name' => $username]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        // Create new client 
        $stmt = $conn->prepare("
            INSERT INTO clients (name, assigned_csr, created_at) 
            VALUES (:name, 'Unassigned', NOW())
        ");
        $stmt->execute([':name' => $username]);

        $client_id = $conn->lastInsertId();
        $assigned_csr = 'Unassigned';
    } else {
        $client_id = $client['id'];
        $assigned_csr = $client['assigned_csr'];
    }

    /* ============================================================
       STEP 2: Check if this client already got a greeting today
    ============================================================ */
    $check = $conn->prepare("
        SELECT id 
        FROM chat 
        WHERE client_id = :cid 
        AND sender_type = 'csr'
        AND DATE(created_at) = CURRENT_DATE
        LIMIT 1
    ");
    $check->execute([':cid' => $client_id]);
    $alreadyGreeted = $check->fetch(PDO::FETCH_ASSOC);

    if ($alreadyGreeted) {
        echo json_encode([
            'status' => 'already_greeted'
        ]);
        exit;
    }

    /* ============================================================
       STEP 3: Pick a random online CSR (PostgreSQL uses RANDOM())
    ============================================================ */
    $csrStmt = $conn->query("
        SELECT username, full_name, email 
        FROM csr_users 
        WHERE is_online = TRUE 
        AND status = 'active'
        ORDER BY RANDOM() 
        LIMIT 1
    ");

    $csr = $csrStmt->fetch(PDO::FETCH_ASSOC);

    if ($csr) {
        /* ----------------------------------------------
           CSR AVAILABLE — Assign client and send greeting
        ---------------------------------------------- */

        $csr_user = $csr['username'];
        $csr_name = $csr['full_name'];

        $message = "👋 Hi $username! This is $csr_name from SkyTruFiber. How can I assist you today?";

        // Assign CSR to this client
        $update = $conn->prepare("
            UPDATE clients 
            SET assigned_csr = :csr,
                assigned_at = NOW()
            WHERE id = :id
        ");
        $update->execute([
            ':csr' => $csr_user,
            ':id' => $client_id
        ]);

        // Insert greeting into chat
        $insert = $conn->prepare("
            INSERT INTO chat 
                (client_id, sender_type, message, assigned_csr, csr_fullname, created_at)
            VALUES 
                (:cid, 'csr', :msg, :csr, :csr_full, NOW())
        ");
        $insert->execute([
            ':cid' => $client_id,
            ':msg' => $message,
            ':csr' => $csr_user,
            ':csr_full' => $csr_name
        ]);

        echo json_encode([
            'status' => 'success',
            'csr' => $csr_name,
            'assigned' => $csr_user,
            'message' => $message
        ]);
        exit;
    }

    /* ============================================================
       STEP 4: NO CSR ONLINE — Send offline message
    ============================================================ */
    $msg = "👋 Hi $username! All our CSRs are currently offline, but we’ll get back to you soon.";

    $insert = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, created_at)
        VALUES (:cid, 'system', :msg, NOW())
    ");
    $insert->execute([':cid' => $client_id, ':msg' => $msg]);

    echo json_encode([
        'status' => 'no_csr',
        'message' => $msg
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage()
    ]);
}
?>
