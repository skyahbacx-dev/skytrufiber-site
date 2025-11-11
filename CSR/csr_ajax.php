<?php
session_start();
include '../db_connect.php';

$csr_user = $_SESSION['csr_user'] ?? '';

$action = $_GET['action'] ?? '';

if ($action === "list") {

    $tab = $_GET['tab'] ?? 'all';

    if ($tab === "mine") {
        $q = $conn->prepare("SELECT id, name, assigned_csr FROM clients WHERE assigned_csr = :csr ORDER BY id ASC");
        $q->execute([':csr' => $csr_user]);
    } else {
        $q = $conn->query("SELECT id, name, assigned_csr FROM clients ORDER BY id ASC");
    }

    $rows = [];

    while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = $r;
    }

    echo json_encode($rows);
    exit;
}

if ($action === "profile") {
    $cid = (int)$_GET['client_id'];

    $q = $conn->prepare("SELECT name, gender, avatar FROM users WHERE id = :id");
    $q->execute([':id' => $cid]);

    echo json_encode($q->fetch(PDO::FETCH_ASSOC));
    exit;
}

if ($action === "messages") {
    $cid = (int)$_GET['client_id'];

    $q = $conn->prepare("
        SELECT ch.*, c.name AS cname, cu.full_name AS csrname
        FROM chat ch
        LEFT JOIN clients c ON ch.client_id = c.id
        LEFT JOIN csr_users cu ON ch.assigned_csr = cu.username
        WHERE ch.client_id = :cid
        ORDER BY ch.created_at ASC
    ");
    $q->execute([':cid' => $cid]);

    $rows = [];

    while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = $r;
    }

    echo json_encode($rows);
    exit;
}

if ($action === "send") {
    $cid = (int)$_POST['client_id'];
    $msg = $_POST['msg'];

    $q = $conn->prepare("
        INSERT INTO chat (client_id, message, sender_type, assigned_csr)
        VALUES (:cid, :msg, 'csr', :csr)
    ");
    $q->execute([
        ':cid' => $cid,
        ':msg' => $msg,
        ':csr' => $csr_user
    ]);

    echo "ok";
    exit;
}

echo "invalid";
