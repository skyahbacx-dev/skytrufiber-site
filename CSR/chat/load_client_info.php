<?php
/* ============================================================
   FORCE CSR SESSION — Prevents client session from overwriting CSR
============================================================ */
ini_set("session.name", "CSRSESSID");
session_start();

/* Prevent caching */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: 0");
header("Pragma: no-cache");

require __DIR__ . "/../../db_connect.php";

/* ============================================================
   VALIDATE CSR SESSION
============================================================ */
if (empty($_SESSION["csr_user"])) {
    echo "Session expired.";
    exit;
}

$csrUser  = $_SESSION["csr_user"];
$clientID = intval($_POST["client_id"] ?? 0);

if ($clientID <= 0) {
    echo "<p>Invalid client.</p>";
    exit;
}

/* ============================================================
   FETCH CLIENT INFORMATION
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        id,
        full_name,
        email,
        account_number,
        district,
        barangay,
        date_installed,
        created_at,
        assigned_csr,
        is_online,
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

/* ============================================================
   CHECK ASSIGNMENT + LOCK STATUS
============================================================ */
$isAssignedToMe = ($u["assigned_csr"] === $csrUser) ? "yes" : "no";
$isLocked       = $u["ticket_lock"] ? "true" : "false";

/* ============================================================
   FETCH MOST RECENT TICKET — BEST PERFORMANCE FIX
============================================================ */
$stmt = $conn->prepare("
    SELECT id, status
    FROM tickets
    WHERE client_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$clientID]);
$ticketData = $stmt->fetch(PDO::FETCH_ASSOC);

$ticketID     = intval($ticketData["id"] ?? 0);
$ticketStatus = strtolower($ticketData["status"] ?? "unresolved");

/* ============================================================
   META OUTPUT — REQUIRED BY chat.js
============================================================ */
echo "
<div id='client-meta'
    data-ticket-id='{$ticketID}'
    data-ticket='{$ticketStatus}'
    data-assigned='{$isAssignedToMe}'
    data-locked='{$isLocked}'>
</div>";
?>

<!-- ============================================================
     CLIENT INFORMATION DISPLAY PANEL
============================================================ -->
<div class="client-info-panel">

    <h3><?= htmlspecialchars($u["full_name"]) ?></h3>

    <p><strong>Account Number:</strong> <?= htmlspecialchars($u["account_number"]) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($u["email"]) ?></p>
    <p><strong>District:</strong> <?= htmlspecialchars($u["district"]) ?></p>
    <p><strong>Barangay:</strong> <?= htmlspecialchars($u["barangay"]) ?></p>
    <p><strong>Date Installed:</strong> <?= htmlspecialchars($u["date_installed"]) ?></p>

    <p><strong>Registered On:</strong>
        <?= date("F d, Y", strtotime($u["created_at"])) ?>
    </p>

    <p><strong>Assigned CSR:</strong>
        <?= $u["assigned_csr"] ? htmlspecialchars($u["assigned_csr"]) : "Unassigned" ?>
    </p>

    <p><strong>Current Ticket:</strong>
        <?= $ticketID ? "#{$ticketID}" : "No Active Ticket" ?>
    </p>

    <p><strong>Status:</strong>
        <span class="ticket-badge <?= $ticketStatus ?>">
            <?= strtoupper($ticketStatus) ?>
        </span>
    </p>

    <p><strong>Online Status:</strong>
        <?= $u["is_online"]
            ? "<span style='color:green;'>● Online</span>"
            : "<span style='color:red;'>● Offline</span>"
        ?>
    </p>

    <p><strong>Locked:</strong> <?= $u["ticket_lock"] ? "Yes" : "No" ?></p>

    <?php if ($u["transfer_request"]): ?>
        <p><strong>Transfer Requested By:</strong>
            <?= htmlspecialchars($u["transfer_request"]) ?>
        </p>
    <?php endif; ?>

</div>
