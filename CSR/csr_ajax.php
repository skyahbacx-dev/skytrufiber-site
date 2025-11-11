<?php
session_start();
include '../db_connect.php';

$csr = $_SESSION['csr_user'] ?? "";  // Example: "CSR WALDO"


/* ✅ LOAD CLIENTS LIST */
if (isset($_GET['clients'])) {

    $tab = $_GET['tab'] ?? "all";

    if ($tab === "mine") {
        // Show only clients assigned to logged-in CSR
        $stmt = $conn->prepare("
            SELECT id, full_name, email, assigned_csr 
            FROM clients 
            WHERE assigned_csr = :csr 
            ORDER BY full_name ASC
        ");
        $stmt->execute([':csr' => $csr]);
    } else {
        // Show all clients
        $stmt = $conn->prepare("
            SELECT id, full_name, email, assigned_csr 
            FROM clients 
            ORDER BY full_name ASC
        ");
        $stmt->execute();
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $assigned = $row['assigned_csr'] ?: "Unassigned";

        // ✅ Assignment button logic
        if ($assigned === "Unassigned") {
            $btn = "<button class='pill green' onclick='assignClient({$row['id']})'>＋</button>";
        }
        else if ($assigned === $csr) {
            $btn = "<button class='pill red' onclick='unassignClient({$row['id']})'>−</button>";
        }
        else {
            $btn = "<button class='pill gray' disabled>🔒</button>";  // other CSR's client
        }

        echo "
        <div class='client-item' 
             data-id='{$row['id']}'
             data-name='".htmlspecialchars($row['full_name'], ENT_QUOTES)."'
             data-csr='{$assigned}'>

            <div>
                <div class='client-name'>{$row['full_name']}</div>
                <div class='client-email'>{$row['email']}</div>
                <div class='client-assign'>Assigned: {$assigned}</div>
            </div>

            <div>{$btn}</div>
        </div>";
    }

    exit;
}



/* ✅ LOAD CHAT HISTORY */
if (isset($_GET['load_chat'])) {

    $cid = (int)$_GET['client_id'];

    $stmt = $conn->prepare("
        SELECT ch.*, c.full_name AS client_name
        FROM chat ch
        JOIN clients c ON c.id = ch.client_id
        WHERE ch.client_id = :cid
        ORDER BY ch.created_at ASC
    ");
    $stmt->execute([':cid' => $cid]);

    $messages = [];

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = [
            "sender"  => $r['sender_type'],
            "message" => $r['message'],
            "time"    => date("M d h:i A", strtotime($r['created_at'])),
            "client"  => $r['client_name'],
            "read"    => $r['is_read']
        ];
    }

    echo json_encode($messages);
    exit;
}



/* ✅ CLIENT PROFILE FIXED */
if (isset($_GET['client_profile'])) {

    $clientId = (int)$_GET['client_id'];

    // ✅ get profile from clients table instead of users table
    $stmt = $conn->prepare("
        SELECT full_name, email, barangay, balance, assigned_csr 
        FROM clients 
        WHERE id = :id LIMIT 1
    ");

    $stmt->execute([':id' => $clientId]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: []);
    exit;
}



/* ✅ SEND MESSAGE — FIXED GET/POST */
if (isset($_POST['send'])) {

    $cid = (int)$_POST['client_id'];
    $msg = trim($_POST['msg']);

    if ($msg === "") {
        echo "empty";
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, assigned_csr, csr_fullname)
        VALUES (:cid, 'csr', :msg, :csr, :csr)
    ");

    $stmt->execute([
        ':cid' => $cid,
        ':msg' => $msg,
        ':csr' => $csr
    ]);

    echo "ok";
    exit;
}



/* ✅ TYPING INDICATOR */
if (isset($_GET['typing'])) {
    echo "ok";
    exit;
}



/* ✅ REMINDERS */
if (isset($_GET['reminders'])) {

    $search = $_GET['q'] ?? "";

    $stmt = $conn->query("
        SELECT full_name, email, date_installed 
        FROM clients
        ORDER BY date_installed DESC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $output = [];

    foreach ($rows as $row) {

        if (!$row['date_installed']) continue;

        if ($search && stripos($row['full_name'].$row['email'], $search) === false) {
            continue;
        }

        $output[] = [
            "name"   => $row['full_name'],
            "email"  => $row['email'],
            "due"    => $row['date_installed'],
            "badges" => "<span class='badge'>1 WEEK</span>"
        ];
    }

    echo json_encode($output);
    exit;
}

?>
