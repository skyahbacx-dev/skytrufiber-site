<?php
// AJAX endpoints for CSR dashboard
session_start();
include '../db_connect.php';

if (!isset($_SESSION['csr_user'])) {
    http_response_code(403);
    echo "unauth";
    exit;
}
$csr_user = $_SESSION['csr_user'];

// Helper: safe column fallback for name/full_name
function name_column_exists($pdo) {
    // we'll attempt to detect; easiest: try a query selecting both using COALESCE in SQL
    return true;
}

/* ------------------------------
   Clients (returns HTML fragment)
   ------------------------------ */
if (isset($_GET['clients'])) {
    $tab = $_GET['tab'] ?? 'all';
    $clients = [];

    // prefer column full_name if exists else name
    // Use COALESCE to handle either column existing (if both exist it uses full_name)
    // But some DB engines will error if column doesn't exist. To be robust: attempt to find a column.
    $cols = [];
    // detect columns in clients table
    try {
        $res = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'clients'")->fetchAll(PDO::FETCH_COLUMN);
    } catch(Exception $e) {
        // fallback: assume 'name' exists
        $res = [];
    }
    $nameCol = (in_array('full_name',$res)) ? 'full_name' : ((in_array('name',$res)) ? 'name' : 'name');

    if ($tab === 'mine') {
        $stmt = $conn->prepare("SELECT id, {$nameCol} AS full_name, assigned_csr, (SELECT email FROM users u WHERE u." . (in_array('full_name',$res) ? 'full_name' : 'full_name') . " = clients.{$nameCol} LIMIT 1) AS email FROM clients WHERE assigned_csr = :csr ORDER BY {$nameCol} ASC");
        $stmt->execute([':csr'=>$csr_user]);
    } else {
        // If column missing, select id, name/full_name
        $stmt = $conn->query("SELECT id, {$nameCol} AS full_name, assigned_csr FROM clients ORDER BY {$nameCol} ASC");
    }

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $assigned = $row['assigned_csr'] ?: 'Unassigned';
        $owned = ($assigned === $csr_user);
        if ($assigned === 'Unassigned') {
            $btn = "<button class='pill green' onclick='assignClient({$row['id']})'>＋</button>";
        } elseif ($owned) {
            $btn = "<button class='pill red' onclick='unassignClient({$row['id']})'>−</button>";
        } else {
            $btn = "<button class='pill gray' disabled>🔒</button>";
        }

        $emailHtml = '';
        if (isset($row['email'])) $emailHtml = "<div class='client-email'>".htmlspecialchars($row['email'])."</div>";

        echo "<div class='client-item' data-id='{$row['id']}' data-name='".htmlspecialchars($row['full_name'],ENT_QUOTES)."' data-csr='".htmlspecialchars($assigned,ENT_QUOTES)."'>
                <div>
                  <div class='client-name'>".htmlspecialchars($row['full_name'])."</div>
                  {$emailHtml}
                  <div class='client-assign'>Assigned: ".htmlspecialchars($assigned)."</div>
                </div>
                <div>{$btn}</div>
              </div>";
    }
    exit;
}

/* ------------------------------
   Load chat messages (JSON)
   ------------------------------ */
if (isset($_GET['load_chat']) && isset($_GET['client_id'])) {
    $cid = (int)$_GET['client_id'];

    $stmt = $conn->prepare("
        SELECT ch.*, c." . ( (function_exists('name_column_exists') ? 'COALESCE(c.full_name, c.name)' : "c.name") ) . " AS client_name
        FROM chat ch
        LEFT JOIN clients c ON c.id = ch.client_id
        WHERE ch.client_id = :cid
        ORDER BY ch.created_at ASC
    ");
    $stmt->execute([':cid'=>$cid]);

    $out = [];
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $out[] = [
            'sender' => $r['sender_type'] ?? 'client',
            'message' => htmlspecialchars($r['message'] ?? ''),
            'time' => isset($r['created_at']) ? date("M d h:i A", strtotime($r['created_at'])) : '',
            'client' => $r['client_name'] ?? '',
            'csr_fullname' => $r['csr_fullname'] ?? null,
            'is_read' => $r['is_read'] ?? 0
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($out);
    exit;
}

/* ------------------------------
   Client profile (JSON)
   ------------------------------ */
if (isset($_GET['client_profile']) && isset($_GET['name'])) {
    $name = trim($_GET['name']);
    $stmt = $conn->prepare("SELECT email, gender, avatar, (is_online IS TRUE) AS online FROM users WHERE full_name = :n LIMIT 1");
    $stmt->execute([':n'=>$name]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    header('Content-Type: application/json');
    echo json_encode($u);
    exit;
}

/* ------------------------------
   Assign / Unassign
   ------------------------------ */
if (isset($_GET['assign']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = (int)($_POST['client_id'] ?? 0);
    if (!$cid) { echo "err"; exit; }

    $chk = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :i");
    $chk->execute([':i'=>$cid]);
    $cur = $chk->fetch(PDO::FETCH_ASSOC);
    if ($cur && $cur['assigned_csr'] && $cur['assigned_csr'] !== 'Unassigned') {
        echo "taken";
        exit;
    }
    $u = $conn->prepare("UPDATE clients SET assigned_csr = :c WHERE id = :i");
    $u->execute([':c'=>$csr_user, ':i'=>$cid]);
    echo "ok";
    exit;
}

if (isset($_GET['unassign']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = (int)($_POST['client_id'] ?? 0);
    if (!$cid) { echo "err"; exit; }
    $u = $conn->prepare("UPDATE clients SET assigned_csr = 'Unassigned' WHERE id = :i AND assigned_csr = :c");
    $u->execute([':i'=>$cid, ':c'=>$csr_user]);
    echo "ok";
    exit;
}

/* ------------------------------
   Send message
   ------------------------------ */
if (isset($_GET['send']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = (int)($_POST['client_id'] ?? 0);
    $msg = trim($_POST['msg'] ?? '');
    if (!$cid || $msg === '') { echo "err"; exit; }

    // check assignment: allow if unassigned or assigned to current csr
    $chk = $conn->prepare("SELECT assigned_csr FROM clients WHERE id = :i LIMIT 1");
    $chk->execute([':i'=>$cid]);
    $cur = $chk->fetch(PDO::FETCH_ASSOC);
    if ($cur && $cur['assigned_csr'] && $cur['assigned_csr'] !== 'Unassigned' && $cur['assigned_csr'] !== $csr_user) {
        http_response_code(403);
        echo "forbidden";
        exit;
    }

    $ins = $conn->prepare("INSERT INTO chat (client_id, sender_type, message, csr_fullname, created_at) VALUES (:cid, 'csr', :msg, :csr, NOW())");
    $ins->execute([':cid'=>$cid, ':msg'=>$msg, ':csr'=>$csr_user]);
    echo "ok";
    exit;
}

/* ------------------------------
   Typing indicator (fire-and-forget)
   ------------------------------ */
if (isset($_GET['typing'])) {
    // You could record typing status in typing_status table if needed.
    echo "ok";
    exit;
}

/* ------------------------------
   Reminders (basic)
   ------------------------------ */
if (isset($_GET['reminders'])) {
    $q = $_GET['q'] ?? '';
    $list = [];
    $stmt = $conn->query("SELECT id, full_name, email, date_installed FROM users ORDER BY full_name ASC");
    while($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!$u['date_installed']) continue;
        if ($q && stripos($u['full_name'].' '.$u['email'], $q) === false) continue;
        $list[] = [
            'name' => $u['full_name'],
            'email' => $u['email'],
            'due' => date('Y-m-d', strtotime($u['date_installed']))
        ];
    }
    header('Content-Type: application/json');
    echo json_encode($list);
    exit;
}

echo "invalid";
exit;
