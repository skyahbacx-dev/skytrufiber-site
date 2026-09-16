<?php
// ------------------------------------------------------------
// Ensure CSR uses its own session — prevents auto logout
// ------------------------------------------------------------
ini_set("session.name", "CSRSESSID");
if (!isset($_SESSION)) session_start();

// Disable caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: 0");
header("Pragma: no-cache");

require __DIR__ . "/../../db_connect.php";

$clientID = intval($_POST["client_id"] ?? 0);
if ($clientID <= 0) exit("<p>Invalid client.</p>");

/* ============================================================
   FETCH CLIENT RECORD
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

if (!$u) exit("<p>Client not found.</p>");

$csrUser = $_SESSION["csr_user"] ?? null;

// If CSR session expired, notify chat.js silently
if (!$csrUser) {
    echo "<div id='client-meta' data-session='expired'></div>";
    exit;
}

$isAssignedToMe = ($u["assigned_csr"] === $csrUser) ? "yes" : "no";
$isLocked       = $u["ticket_lock"] ? "true" : "false";

/* ============================================================
   FETCH LATEST TICKET — Uses ID (fastest + accurate)
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        id,
        status
    FROM tickets
    WHERE client_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$clientID]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

$ticketID     = intval($ticket["id"] ?? 0);
$ticketStatus = strtolower($ticket["status"] ?? "unresolved");

/* ============================================================
   OUTPUT META FOR JAVASCRIPT — controls send button,
   locking, resolved state, assignment, etc.
============================================================ */
echo "
<div id='client-meta'
    data-session='active'
    data-ticket-id='{$ticketID}'
    data-ticket='{$ticketStatus}'
    data-assigned='{$isAssignedToMe}'
    data-locked='{$isLocked}'>
</div>
";
?>

<!-- ============================================================
     CLIENT INFORMATION PANEL
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

    <p><strong>Locked:</strong>
        <?= $u["ticket_lock"] ? "Yes" : "No" ?>
    </p>

    <?php if ($u["transfer_request"]): ?>
        <p><strong>Transfer Requested By:</strong>
            <?= htmlspecialchars($u["transfer_request"]) ?>
        </p>
    <?php endif; ?>

</div>
