<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);

if (!$csrUser || $clientID <= 0) {
    echo "<p>Error loading client data.</p>";
    exit;
}

/* ==========================================================
   FETCH CLIENT INFO
========================================================== */
$stmt = $conn->prepare("
    SELECT 
        full_name,
        email,
        contact,
        ticket_status,
        assigned_csr,
        ticket_lock,
        transfer_request
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$clientID]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    echo "<p>Client not found.</p>";
    exit;
}

$name       = htmlspecialchars($c["full_name"]);
$email      = htmlspecialchars($c["email"]);
$contact    = htmlspecialchars($c["contact"]);
$status     = strtolower($c["ticket_status"]);
$assignedTo = $c["assigned_csr"];
$locked     = intval($c["ticket_lock"]) === 1;
$requesting = $c["transfer_request"];

/* ===============================  
   STATUS BADGE
================================ */
$badge = "";
switch ($status) {
    case "resolved":
        $badge = "<span class='ticket-badge resolved'>RESOLVED</span>";
        break;
    case "pending":
        $badge = "<span class='ticket-badge pending'>PENDING</span>";
        break;
    default:
        $badge = "<span class='ticket-badge unresolved'>UNRESOLVED</span>";
        break;
}

/* ==========================================================
   ASSIGNMENT LOGIC + BUTTONS
========================================================== */
$actionBtn = "";
$lockHint  = "";

/* CASE 1 — Client has no assigned CSR → show ➕ */
if ($assignedTo === null) {
    $actionBtn = "<button class='assign-btn big' data-id='$clientID'>Assign to me ➕</button>";
}

/* CASE 2 — Assigned to ME → show ➖ (unassign) */
else if ($assignedTo === $csrUser) {
    $actionBtn = "<button class='unassign-btn big' data-id='$clientID'>Unassign ➖</button>";
    $lockHint = $locked ? "<p class='lock-note'>🔒 You locked this ticket.</p>" : "";
}

/* CASE 3 — Assigned to ANOTHER CSR → show 🔒 */
else {
    $actionBtn = "
        <button class='request-transfer-btn big' data-id='$clientID'>
            Request Transfer 🔒
        </button>
        <p style='font-size:13px;color:#666;margin-top:6px;'>
            Assigned to: <strong>$assignedTo</strong>
        </p>
    ";
}

/* ==========================================================
   TRANSFER REQUEST PANEL (only visible to assigned CSR)
========================================================== */
$transferBox = "";

if ($requesting && $assignedTo === $csrUser) {
    $transferBox = "
        <div class='transfer-box'>
            <p>Transfer requested by <strong>$requesting</strong></p>
            <button class='approve-transfer-btn' data-id='$clientID'>Approve</button>
            <button class='deny-transfer-btn' data-id='$clientID'>Deny</button>
        </div>
    ";
}
?>

<!-- ========================================================
     OUTPUT TO RIGHT PANEL
======================================================== -->
<div id="client-meta"
     data-ticket="<?= $status ?>"
     data-assigned="<?= ($assignedTo === $csrUser ? 'yes' : 'no') ?>"
     data-locked="<?= $locked ? 'true' : 'false' ?>">

    <h2><?= $name ?></h2>
    <?= $badge ?>

    <hr>

    <p><strong>Email:</strong> <?= $email ?></p>
    <p><strong>Contact:</strong> <?= $contact ?></p>

    <hr>

    <h3>Assignment</h3>

    <?= $actionBtn ?>
    <?= $lockHint ?>

    <?php if ($assignedTo): ?>
        <p style="margin-top:8px;">
            Current CSR: <strong><?= $assignedTo ?></strong>
        </p>
    <?php endif; ?>

    <?= $transferBox ?>

</div>

<style>
    #client-meta h2 { margin-bottom: 5px; }
    .ticket-badge {
        padding: 4px 10px;
        border-radius: 10px;
        font-size: 13px;
        color: white;
    }
    .ticket-badge.unresolved { background:#d63031; }
    .ticket-badge.pending { background:#f1c40f; color:#222; }
    .ticket-badge.resolved { background:#2ecc71; }

    .big {
        padding: 10px 16px;
        font-size: 14px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        width: 100%;
        margin-top: 6px;
    }

    .assign-btn { background:#1abc9c; color:white; }
    .unassign-btn { background:#e74c3c; color:white; }
    .request-transfer-btn { background:#95a5a6; color:white; }

    .transfer-box {
        margin-top: 14px;
        padding: 10px;
        border-radius: 8px;
        background:#f8f8f8;
        border:1px solid #ccc;
    }
    .transfer-box button {
        margin-right: 8px;
        padding: 6px 12px;
        border:none;
        border-radius:6px;
        cursor:pointer;
    }
    .approve-transfer-btn { background:#27ae60; color:white; }
    .deny-transfer-btn { background:#c0392b; color:white; }

    .lock-note { margin-top:6px; color:#888; }
</style>
