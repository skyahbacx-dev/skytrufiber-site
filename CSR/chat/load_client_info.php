<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$clientID = intval($_POST["client_id"] ?? 0);
if ($clientID <= 0) {
    echo "<p>Invalid client.</p>";
    exit;
}

/* ============================================================
   FETCH CLIENT DETAILS
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        id,
        full_name,
        email,
        district,
        barangay,
        date_installed,
        assigned_csr,
        is_online,
        is_locked,
        ticket_status,
        ticket_lock,
        transfer_request
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$clientID]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u) {
    echo "<p>Client not found.</p>";
    exit;
}

$csrUser = $_SESSION['csr_user'] ?? '';
$isAssignedToMe = ($u["assigned_csr"] === $csrUser) ? "yes" : "no";
$isLocked = ($u["ticket_lock"] == 1 ? "true" : "false");

/* ============================================================
   META FOR JS
============================================================ */
echo "
<div id='client-meta'
    data-ticket='{$u["ticket_status"]}'
    data-assigned='{$isAssignedToMe}'
    data-locked='{$isLocked}'>
</div>
";
?>
<div class="client-info-panel">
    <h3><?= htmlspecialchars($u["full_name"]) ?></h3>

    <p><strong>Email:</strong> <?= htmlspecialchars($u["email"]) ?></p>
    <p><strong>District:</strong> <?= htmlspecialchars($u["district"]) ?></p>
    <p><strong>Barangay:</strong> <?= htmlspecialchars($u["barangay"]) ?></p>
    <p><strong>Date Installed:</strong> <?= htmlspecialchars($u["date_installed"]) ?></p>

    <p><strong>Assigned CSR:</strong> 
        <?= $u["assigned_csr"] ? htmlspecialchars($u["assigned_csr"]) : "Unassigned" ?>
    </p>

    <p><strong>Status:</strong> 
        <span class="ticket-badge <?= strtolower($u["ticket_status"]) ?>">
            <?= strtoupper($u["ticket_status"]) ?>
        </span>
    </p>

    <?php if ($u["transfer_request"]) : ?>
        <p><strong>Transfer Requested By:</strong> <?= htmlspecialchars($u["transfer_request"]) ?></p>
    <?php endif; ?>

    <hr>

    <p><strong>Online Status:</strong>
        <?= $u["is_online"] ? "<span style='color:green;'>● Online</span>" : "<span style='color:red;'>● Offline</span>" ?>
    </p>

    <p><strong>Locked:</strong>
        <?= $u["ticket_lock"] ? "Yes (another CSR is editing)" : "No" ?>
    </p>
</div>
