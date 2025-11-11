<?php
session_start();
include '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['csr_user'])) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$csr_user = $_SESSION['csr_user'];

/* ROUTER */
$action = $_GET['action'] ?? null;

switch ($action) {

    /* GET CLIENT LIST */
    case 'clients':
        $tab = $_GET['tab'] ?? 'all';

        if ($tab === 'mine') {
            $stmt = $conn->prepare("
                SELECT c.id, c.name, c.assigned_csr, u.email
                FROM clients c
                LEFT JOIN users u ON u.full_name = c.name
                WHERE c.assigned_csr = :csr
                ORDER BY c.id ASC
            ");
            $stmt->execute(['csr' => $csr_user]);
        } else {
            $stmt = $conn->query("
                SELECT c.id, c.name, c.assigned_csr, u.email
                FROM clients c
                LEFT JOIN users u ON u.full_name = c.name
                ORDER BY c.id ASC
            ");
        }

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    /* LOAD CHAT */
    case 'load_chat':
        $cid = (int)($_GET['client_id'] ?? 0);

        $stmt = $conn->prepare("
            SELECT ch.message, ch.sender_type, ch.created_at,
                   c.name AS client_name, 
                   COALESCE(ch.csr_fullname, cu.full_name) AS csr_fullname
            FROM chat ch
            JOIN clients c ON c.id = ch.client_id
            LEFT JOIN csr_users cu ON cu.username = :csr
            WHERE ch.client_id = :cid
            ORDER BY ch.created_at ASC
        ");
        $stmt->execute([
            'cid' => $cid,
            'csr' => $csr_user
        ]);

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    /* SEND MESSAGE */
    case 'send':
        $cid = (int)($_POST['client_id'] ?? 0);
        $msg = trim($_POST['message'] ?? '');

        if (!$msg) {
            echo json_encode(['error' => 'empty']);
            break;
        }

        // Get CSR fullname
        $csr_stmt = $conn->prepare("SELECT full_name FROM csr_users WHERE username = :u");
        $csr_stmt->execute(['u' => $csr_user]);
        $csr = $csr_stmt->fetch(PDO::FETCH_ASSOC);
        $csr_name = $csr['full_name'] ?? $csr_user;

        $stmt = $conn->prepare("
            INSERT INTO chat (client_id,message,sender_type,csr_fullname,assigned_csr)
            VALUES (:cid,:msg,'csr',:fullname,:csr)
        ");
        $stmt->execute([
            'cid' => $cid,
            'msg' => $msg,
            'fullname' => $csr_name,
            'csr' => $csr_user
        ]);

        echo json_encode(['status' => 'ok']);
        break;

    default:
        echo json_encode(['error' => 'invalid action']);
        break;
}
