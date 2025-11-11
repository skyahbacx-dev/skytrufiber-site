<?php
session_start();
include '../db_connect.php';

$csr = $_SESSION['csr_user'] ?? "";

/* ✅ Load clients */
if (isset($_GET['clients'])) {
    $tab = $_GET['tab'] ?? "all";

    if ($tab === "mine") {
        $stmt = $conn->prepare("SELECT * FROM clients WHERE assigned_csr = :csr ORDER BY full_name ASC");
        $stmt->execute([':csr'=>$csr]);
    } else {
        $stmt = $conn->query("SELECT * FROM clients ORDER BY full_name ASC");
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $assigned = $row['assigned_csr'] ?: "Unassigned";
        $btn = ($assigned === "Unassigned") ?
            "<button class='pill green' onclick='assignClient({$row['id']})'>＋</button>" :
            ($assigned === $csr ? "<button class='pill red' onclick='unassignClient({$row['id']})'>−</button>" :
            "<button class='pill gray' disabled>🔒</button>");

        echo "
        <div class='client-item' data-id='{$row['id']}'
             data-name='".htmlspecialchars($row['full_name'],ENT_QUOTES)."'
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

/* ✅ Load chat */
if (isset($_GET['load_chat'])) {
    $cid = (int)$_GET['client_id'];

    $stmt = $conn->prepare("
        SELECT ch.*, c.full_name AS client
        FROM chat ch
        JOIN clients c ON c.id = ch.client_id
        WHERE ch.client_id = :cid
        ORDER BY created_at ASC
    ");
    $stmt->execute([':cid'=>$cid]);

    $out = [];
    while ($r=$stmt->fetch(PDO::FETCH_ASSOC)) {
        $out[] = [
            "sender"=>$r['sender_type'],
            "message"=>$r['message'],
            "time"=>date("M d h:i A", strtotime($r['created_at'])),
            "client"=>$r['client'],
            "read"=>$r['is_read']
        ];
    }

    echo json_encode($out);
    exit;
}

/* ✅ Client profile */
if (isset($_GET['client_profile'])) {
    $name = $_GET['name'];

    $stmt = $conn->prepare("SELECT email, gender, avatar, online FROM users WHERE full_name = :n LIMIT 1");
    $stmt->execute([':n'=>$name]);

    $out = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    echo json_encode($out);
    exit;
}

/* ✅ Send message */
if (isset($_GET['send'])) {
    $cid = $_POST['client_id'];
    $msg = $_POST['msg'];

    $stmt = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, csr_fullname) VALUES (:i,'csr',:m,:csr)");
    $stmt->execute([
        ':i'=>$cid,
        ':m'=>$msg,
        ':csr'=>$csr
    ]);

    echo "ok";
    exit;
}

/* ✅ Typing indicator */
if (isset($_GET['typing'])) {
    echo "ok";
    exit;
}

/* ✅ Reminders */
if (isset($_GET['reminders'])) {
    $q = $_GET['q'] ?? "";

    $u = $conn->query("SELECT full_name, email, date_installed FROM users")->fetchAll(PDO::FETCH_ASSOC);

    $out = [];

    foreach ($u as $usr) {
        if (!$usr['date_installed']) continue;
        if ($q && stripos($usr['full_name'].$usr['email'], $q) === false) continue;

        $out[] = [
            "name"=>$usr['full_name'],
            "email"=>$usr['email'],
            "due"=>$usr['date_installed'],
            "badges"=>"<span class='badge'>1 WEEK</span>"
        ];
    }

    echo json_encode($out);
    exit;
}

?>
