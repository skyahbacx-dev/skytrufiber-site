<?php
session_start();
include '../db_connect.php';

$csr = $_SESSION['csr_user'] ?? "";

/* ===========================
   CLIENT LIST
=========================== */
if (isset($_GET['clients'])) {
    $tab = $_GET['tab'] ?? "all";

    if ($tab === "mine") {
        $stmt = $conn->prepare("SELECT * FROM clients WHERE assigned_csr = :csr ORDER BY full_name ASC");
        $stmt->execute([':csr'=>$csr]);
    } else {
        $stmt = $conn->query("SELECT * FROM clients ORDER BY full_name ASC");
    }

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $assigned = $r['assigned_csr'] ?: "Unassigned";

        if ($assigned === "Unassigned") {
            $btn = "<button class='pill green' onclick='assignClient({$r['id']})'>＋</button>";
        } else if ($assigned === $csr) {
            $btn = "<button class='pill red' onclick='unassignClient({$r['id']})'>−</button>";
        } else {
            $btn = "<button class='pill gray' disabled>🔒</button>";
        }

        echo "
        <div class='client-item' 
             data-id='{$r['id']}'
             data-name='".htmlspecialchars($r['full_name'],ENT_QUOTES)."'
             data-csr='{$assigned}'>

            <div class='client-name'>{$r['full_name']}</div>
            <div class='client-email'>{$r['email']}</div>
            <div class='client-assign'>Assigned: {$assigned}</div>
            <div>{$btn}</div>
        </div>
        ";
    }
    exit;
}

/* ===========================
   LOAD CHAT
=========================== */
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

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
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

/* ===========================
   PROFILE
=========================== */
if (isset($_GET['client_profile'])) {
    $name = $_GET['name'];

    $stmt = $conn->prepare("SELECT email, gender, avatar FROM users WHERE full_name = :n LIMIT 1");
    $stmt->execute([':n'=>$name]);

    $res = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode($res);
    exit;
}

/* ===========================
   SEND MESSAGE
=========================== */
if (isset($_GET['send'])) {
    $cid = $_POST['client_id'];
    $msg = $_POST['msg'];

    $stmt = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, csr_fullname)
        VALUES (:i,'csr',:m,:csr)");
    $stmt->execute([
        ':i'=>$cid,
        ':m'=>$msg,
        ':csr'=>$csr
    ]);

    echo "ok";
    exit;
}

/* ===========================
   ASSIGN/UNASSIGN
=========================== */
if (isset($_GET['assign'])) {
    $cid = $_POST['client_id'];

    $chk = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :i");
    $chk->execute([':i'=>$cid]);
    $row = $chk->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['assigned_csr'] && $row['assigned_csr'] !== "Unassigned") {
        echo "taken";
        exit;
    }

    $stmt = $conn->prepare("UPDATE clients SET assigned_csr = :csr WHERE id = :i");
    $stmt->execute([':csr'=>$csr, ':i'=>$cid]);

    echo "ok";
    exit;
}

if (isset($_GET['unassign'])) {
    $cid = $_POST['client_id'];

    $stmt = $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned' WHERE id = :i AND assigned_csr = :csr");
    $stmt->execute([':i'=>$cid, ':csr'=>$csr]);

    echo "ok";
    exit;
}

/* ===========================
   REMINDERS
=========================== */
if (isset($_GET['reminders'])) {
    $q = $_GET['q'] ?? "";

    $u = $conn->query("SELECT full_name, email, date_installed FROM users ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($u as $usr) {
        if (!$usr['date_installed']) continue;

        if ($q && stripos($usr['full_name'].$usr['email'], $q) === false) continue;

        $out[] = [
            "name"=>$usr['full_name'],
            "email"=>$usr['email'],
            "due"=>$usr['date_installed'],
            "badges"=>"<span class='badge upcoming'>Upcoming</span>"
        ];
    }

    echo json_encode($out);
    exit;
}

echo "invalid request";
