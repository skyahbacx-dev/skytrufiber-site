<?php
session_start();
include '../db_connect.php';

$csr = $_SESSION['csr_user'] ?? "";

/* ✅ Load clients from users table */
if (isset($_GET['clients'])) {
    $tab = $_GET['tab'] ?? "all";

    if ($tab === "mine") {
        $stmt = $conn->prepare("SELECT * FROM users WHERE assigned_csr = :csr ORDER BY full_name ASC");
        $stmt->execute([':csr'=>$csr]);
    } else {
        $stmt = $conn->query("SELECT * FROM users ORDER BY full_name ASC");
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $assigned = $row['assigned_csr'] ?: "Unassigned";

        $btn = ($assigned === "Unassigned")
            ? "<button class='pill green' onclick='assignClient({$row['id']})'>＋</button>"
            : ($assigned === $csr
                ? "<button class='pill red' onclick='unassignClient({$row['id']})'>−</button>"
                : "<button class='pill gray' disabled>🔒</button>");

        echo "
        <div class='client-item' onclick='openChat({$row['id']})'>
            <img src='{$row['avatar']}' class='list-avatar'>
            <div>
                <div class='client-name'>".htmlspecialchars($row['full_name'])."</div>
                <div class='client-email'>{$row['email']}</div>
                <div class='client-assign'>Assigned: {$assigned}</div>
            </div>
            <div>{$btn}</div>
        </div>
        ";
    }
    exit;
}

/* ✅ Load chat messages */
if (isset($_GET['load_chat'])) {
    $cid = (int)$_GET['client_id'];

    $stmt = $conn->prepare("
        SELECT ch.*, u.full_name AS client
        FROM chat ch
        JOIN users u ON u.id = ch.client_id
        WHERE ch.client_id = :cid
        ORDER BY ch.created_at ASC
    ");
    $stmt->execute([':cid'=>$cid]);

    $out = [];
    while ($r=$stmt->fetch(PDO::FETCH_ASSOC)) {
        $out[] = [
            "sender"=>$r['sender_type'],
            "message"=>$r['message'],
            "time"=>date("M d h:i A", strtotime($r['created_at'])),
            "client"=>$r['client']
        ];
    }
    echo json_encode($out);
    exit;
}

/* ✅ Send message */
if (isset($_GET['send'])) {
    $cid = $_POST['client_id'];
    $msg = $_POST['msg'];

    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, csr_fullname)
        VALUES (:i,'csr',:m,:csr)
    ");
    $stmt->execute([
        ':i'=>$cid,
        ':m'=>$msg,
        ':csr'=>$csr
    ]);

    echo "ok";
    exit;
}

/* ✅ Assign client */
if (isset($_GET['assign'])) {
    $cid = (int)$_POST['id'];
    $stmt = $conn->prepare("UPDATE users SET assigned_csr = :csr WHERE id = :id");
    $stmt->execute([':csr'=>$csr, ':id'=>$cid]);
    echo "ok";
    exit;
}

/* ✅ Unassign client */
if (isset($_GET['unassign'])) {
    $cid = (int)$_POST['id'];
    $stmt = $conn->prepare("UPDATE users SET assigned_csr = NULL WHERE id = :id");
    $stmt->execute([':id'=>$cid]);
    echo "ok";
    exit;
}

/* ✅ Reminders */
if (isset($_GET['reminders'])) {
    $q = $_GET['q'] ?? "";

    $stmt = $conn->query("
        SELECT full_name, email, date_installed
        FROM users
        ORDER BY date_installed DESC
    ");

    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!$row['date_installed']) continue;
        if ($q && stripos($row['full_name'].$row['email'], $q) === false) continue;

        $out[] = [
            "name"=>$row['full_name'],
            "email"=>$row['email'],
            "due"=>$row['date_installed']
        ];
    }

    echo json_encode($out);
    exit;
}

echo "invalid";
?>
