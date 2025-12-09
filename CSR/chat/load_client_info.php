<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$clientID = intval($_POST["client_id"] ?? 0);
$currentCSR = $_SESSION["csr_user"] ?? null;

if ($clientID <= 0) {
    echo "<p>Error: Invalid client.</p>";
    exit;
}

// ============================================================
// FETCH CLIENT + TICKET DATA
// ============================================================
$stmt = $conn->prepare("
    SELECT 
        id,
        full_name,
        email,
        contact_number,
        address,
        assigned_csr,
        ticket_status,
        ticket_lock,
        transfer_request
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$clientID]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    echo "<p>Client not found.</p>";
    exit;
}

$name        = htmlspecialchars($client["full_name"]);
$email       = htmlspecialchars($client["email"]);
$contact     = htmlspecialchars($client["contact_number"]);
$address     = htmlspecialchars($client["address"]);
$status      = $client["ticket_status"];
$assigned    = $client["assigned_csr"];
$isLocked    = ($client["ticket_lock"] == 1);
$transferReq = $client["transfer_request"];

// Current CSR authority
$isMine = ($assigned === $currentCSR);
$someoneElseOwns = ($assigned && !$isMine);

// Lock icon text
$lockIcon  = $isLocked ? "🔒 Locked" : "🔓 Unlocked";
$lockColor = $isLocked ? "color:#e74c3c;" : "color:#27ae60;";
?>

<div id="client-meta"
     data-ticket="<?= $status ?>"
     data-assigned="<?= $isMine ? 'yes' : 'no' ?>"
     data-locked="<?= $isLocked ? 'true' : 'false' ?>"
     data-transfer="<?= $transferReq ? $transferReq : '' ?>"
     style="display:none;">
</div>

<div class="client-info-box">

    <h2><?= $name ?></h2>
    <p><strong>Email:</strong> <?= $email ?></p>
    <p><strong>Contact:</strong> <?= $contact ?></p>
    <p><strong>Address:</strong> <?= $address ?></p>

    <hr>

    <!-- ========================================== -->
    <!-- ASSIGNMENT STATUS -->
    <!-- ========================================== -->
    <p><strong>Assigned CSR:</strong>
        <?= $assigned ? htmlspecialchars($assigned) : "None" ?>
    </p>

    <?php if (!$assigned): ?>
        <button class="assign-btn" data-id="<?= $clientID ?>">
            ➕ Assign to Me
        </button>

    <?php elseif ($isMine): ?>
        <button class="unassign-btn" data-id="<?= $clientID ?>">
            ➖ Unassign
        </button>

    <?php else: ?>
        <button class="request-transfer-btn" 
                data-id="<?= $clientID ?>" 
                data-current="<?= $assigned ?>">
            🔄 Request Transfer from <?= htmlspecialchars($assigned) ?>
        </button>
    <?php endif; ?>

    <?php if ($transferReq && $transferReq === $currentCSR): ?>
        <div class="transfer-box">
            <p>⚠ <strong>Transfer requested to you.</strong></p>
            <button class="transfer-accept-btn" data-id="<?= $clientID ?>">✔ Accept</button>
            <button class="transfer-deny-btn" data-id="<?= $clientID ?>">✖ Deny</button>
        </div>
    <?php endif; ?>

    <hr>

    <!-- ========================================== -->
    <!-- TICKET STATUS -->
    <!-- ========================================== -->
    <p><strong>Ticket Status:</strong></p>
    <select id="ticket-status-dropdown" 
        <?= !$isMine ? "disabled" : "" ?>>
        <option value="unresolved" <?= $status === "unresolved" ? "selected" : "" ?>>
            Unresolved
        </option>

        <option value="pending" <?= $status === "pending" ? "selected" : "" ?>>
            Pending (On Hold)
        </option>

        <option value="resolved" <?= $status === "resolved" ? "selected" : "" ?>>
            Resolved
        </option>
    </select>

    <p style="font-size:12px;color:#888;margin-top:5px;">
        * Pending means the CSR is coordinating with field technicians.
    </p>

    <hr>

    <!-- ========================================== -->
    <!-- LOCK CONTROL -->
    <!-- ========================================== -->
    <p><strong>Chat Lock:</strong> <span style="<?= $lockColor ?>"><?= $lockIcon ?></span></p>

    <button class="lock-toggle-btn" data-id="<?= $clientID ?>">
        <?= $isLocked ? "Unlock Chat" : "Lock Chat" ?>
    </button>

    <p style="font-size:12px;color:#999;margin-top:5px;">
        * When locked, all CSR chat input is disabled.
    </p>

</div>
