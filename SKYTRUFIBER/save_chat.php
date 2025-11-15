<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

$sender_type  = $_POST['sender_type'] ?? 'client';
$message      = trim($_POST['message'] ?? '');
$username     = $_SESSION['name'] ?? ($_POST['username'] ?? '');
$csr_user     = $_POST['csr_user'] ?? '';
$csr_fullname = $_POST['csr_fullname'] ?? '';

if ($message === '' && empty($_FILES['file']['name'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Empty message']);
    exit;
}

/* File upload config */
$file_path = null;
$file_name = null;

if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/upload/chat/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $orig = $_FILES['file']['name'];
    $ext  = pathinfo($orig, PATHINFO_EXTENSION);
    $base = bin2hex(random_bytes(8));
    $newName = $base . ($ext ? ".".$ext : "");
    $target = $uploadDir . $newName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
        // public path from CSR folder
        $file_path = 'upload/chat/' . $newName;
        $file_name = $orig;
    }
}

/* ===========================================================
   CLIENT MESSAGE
=========================================================== */
if ($sender_type === 'client') {
    $stmt = $conn->prepare("SELECT id, assigned_csr FROM clients WHERE name = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // pick random active CSR
        $csrQ = $conn->query("SELECT username, full_name FROM csr_users WHERE status='active' AND is_online=TRUE ORDER BY RANDOM() LIMIT 1");
        $csr = $csrQ->fetch(PDO::FETCH_ASSOC);
        $assigned_csr = $csr['username'] ?? 'Unassigned';
        $assigned_csr_full = $csr['full_name'] ?? '';

        $ins = $conn->prepare("INSERT INTO clients (name, assigned_csr, created_at)
                               VALUES (:n, :csr, NOW()) RETURNING id");
        $ins->execute([':n' => $username, ':csr' => $assigned_csr]);
        $client_id = $ins->fetchColumn();
    } else {
        $client_id = $row['id'];
        $assigned_csr = $row['assigned_csr'];
        if ($assigned_csr) {
            $csrNameQ = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :c LIMIT 1");
            $csrNameQ->execute([':c' => $assigned_csr]);
            $assigned_csr_full = $csrNameQ->fetchColumn() ?: $assigned_csr;
        } else {
            $assigned_csr_full = '';
        }
    }

    $stmt2 = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, file_path, file_name, created_at)
        VALUES (:cid, 'client', :msg, :fpath, :fname, NOW())
    ");
    $stmt2->execute([
        ':cid'   => $client_id,
        ':msg'   => $message,
        ':fpath' => $file_path,
        ':fname' => $file_name
    ]);

    // greeting on first interaction
    $count = $conn->query("SELECT COUNT(*) FROM chat WHERE client_id = {$client_id}")->fetchColumn();
    if ($count <= 2 && !empty($assigned_csr) && $assigned_csr !== 'Unassigned') {
        $greet = "👋 Hi $username! This is $assigned_csr_full from SkyTruFiber. How can I assist you today?";
        $stmt3 = $conn->prepare("
            INSERT INTO chat (client_id, sender_type, message, assigned_csr, csr_fullname, created_at)
            VALUES (:cid, 'csr', :msg, :csr, :csr_full, NOW())
        ");
        $stmt3->execute([
            ':cid'      => $client_id,
            ':msg'      => $greet,
            ':csr'      => $assigned_csr,
            ':csr_full' => $assigned_csr_full
        ]);
    }

    echo json_encode(['status' => 'ok', 'client_id' => $client_id]);
    exit;
}

/* ===========================================================
   CSR MESSAGE
=========================================================== */
if ($sender_type === 'csr' && isset($_POST['client_id'])) {
    $client_id = (int)$_POST['client_id'];

    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, assigned_csr, csr_fullname, file_path, file_name, created_at)
        VALUES (:cid, 'csr', :msg, :csr, :csr_full, :fpath, :fname, NOW())
    ");
    $stmt->execute([
        ':cid'      => $client_id,
        ':msg'      => $message,
        ':csr'      => $csr_user,
        ':csr_full' => $csr_fullname,
        ':fpath'    => $file_path,
        ':fname'    => $file_name
    ]);

    echo json_encode(['status' => 'ok']);
    exit;
}

echo json_encode(['status' => 'error', 'msg' => 'Invalid request']);
