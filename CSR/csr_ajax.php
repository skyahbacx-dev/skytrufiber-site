<?php
session_start();
include '../db_connect.php';

$csr_user = $_SESSION['csr_user'] ?? null;

$action = $_GET['action'] ?? null;

header("Content-Type: application/json");

// ----------------- LOAD CLIENTS ------------------
if ($action === "clients") {
    $tab = $_GET['tab'] ?? 'all';
    $where = ($tab === "mine") ? "WHERE assigned_csr = :csr" : "";

    $stmt = $conn->prepare("SELECT id, name, email, assigned_csr FROM clients $where ORDER BY id ASC");
    if ($tab === "mine") {
        $stmt->execute([':csr' => $csr_user]);
    } else {
        $stmt->execute();
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $assigned = $row['assigned_csr'] ?: "Unassigned";
        echo "
        <div class='client-item' data-id='{$row["id"]}' data-name='".htmlspecialchars($row["name"])."' data-csr='{$assigned}'>
            <strong>{$row["name"]}</strong><br>
            <small>{$row["email"]}</small><br>
            <small>Assigned: {$assigned}</small>
        </div>";
    }
    exit;
}

// ----------------- LOAD CHAT ------------------
if ($action === "load_chat" && isset($_GET['client_id'])) {
    $cid = (int)$_GET['client_id'];

    $stmt = $conn->prepare("
        SELECT ch.*, 
        CASE WHEN ch.sender_type='csr' THEN csr.full_name ELSE u.full_name END AS sender_name
        FROM chat ch
        LEFT JOIN csr_users csr ON csr.username = ch.assigned_csr
        LEFT JOIN users u ON u.full_name = ch.client_name
        WHERE ch.client_id = :cid
        ORDER BY ch.created_at ASC
    ");
    $stmt->execute([':cid' => $cid]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

// ----------------- LOAD CLIENT PROFILE ------------------
if ($action === "client_profile" && isset($_GET['name'])) {
    $name = $_GET['name'];

    $stmt = $conn->prepare("SELECT gender, avatar FROM users WHERE full_name = :n LIMIT 1");
    $stmt->execute([':n'=>$name]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($data);
    exit;
}

// ----------------- SEND MESSAGE ------------------
if ($action === "send") {
    $cid = $_POST['client_id'];
    $msg = $_POST['message'];

    // Insert chat record
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, message, sender_type, assigned_csr, created_at)
        VALUES (:cid, :msg, 'csr', :csr, NOW())
    ");
    $stmt->execute([
        ':cid'=>$cid,
        ':msg'=>$msg,
        ':csr'=>$csr_user
    ]);

    echo json_encode(["status"=>"ok"]);
    exit;
}

// ----------------- REMINDERS ------------------
if ($action === "reminders") {
    echo json_encode([]); // simplified
    exit;
}

echo json_encode(["error"=>"Invalid action"]);
