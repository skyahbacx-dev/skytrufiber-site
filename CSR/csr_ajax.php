<?php
session_start();
include '../db_connect.php';

$csr_user = $_SESSION['csr_user'] ?? null;

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

/* ======================
   LOAD CLIENT LIST
 ====================== */
if ($action === "clients") {

    $tab = $_GET['tab'] ?? "all";

    if ($tab === "mine") {
        $stmt = $conn->prepare("
            SELECT c.id, 
                   c.name,
                   c.assigned_csr,
                   u.email
            FROM clients c
            LEFT JOIN users u ON u.full_name = c.name
            WHERE c.assigned_csr = :csr
            ORDER BY c.id ASC
        ");
        $stmt->execute([':csr' => $csr_user]);
    } else {
        $stmt = $conn->query("
            SELECT c.id, 
                   c.name,
                   c.assigned_csr,
                   u.email
            FROM clients c
            LEFT JOIN users u ON u.full_name = c.name
            ORDER BY c.id ASC
        ");
    }

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ======================
   LOAD CHAT
 ====================== */
if ($action === "load_chat" && isset($_GET['client_id'])) {

    $stmt = $conn->prepare("
        SELECT ch.message,
               ch.sender_type,
               ch.created_at,
               c.name AS client_name,
               ch.csr_fullname
        FROM chat ch
        JOIN clients c ON c.id = ch.client_id
        WHERE ch.client_id = :id
        ORDER BY ch.created_at ASC
    ");
    $stmt->execute([":id" => $_GET['client_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ======================
   SEND MESSAGE
 ====================== */
if ($action === "send" && isset($_POST['client_id']) && isset($_POST['message'])) {

    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, message, sender_type, csr_fullname, assigned_csr)
        VALUES (:cid, :msg, 'csr', :csrname, :assigned)
    ");
    $stmt->execute([
        ":cid" => $_POST["client_id"],
        ":msg" => $_POST["message"],
        ":csrname" => $_SESSION["csr_fullname"] ?? $csr_user,
        ":assigned" => $csr_user
    ]);

    echo json_encode(["status" => "ok"]);
    exit;
}

echo json_encode(["error" => "unknown"]);
