<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$clientID = intval($_POST["client_id"] ?? 0);
$csrUser  = $_SESSION["csr_user"] ?? null;

if ($clientID <= 0) {
    echo "<p>Error loading client info.</p>";
    exit;
}

// ----------------------------------------------------------
// FETCH CLIENT INFO
// ----------------------------------------------------------
$stmt = $conn->prepare("
    SELECT 
        full_name,
        email,
        account_number,
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

$assignedCSR   = $client["assigned_csr"];
$ticketStatus  = $client["ticket_status"];
$ticketLock    = $client["ticket_lock"];
$transferReq   = $client["transfer_request"];
$isAssignedToMe = ($assignedCSR === $csrUser);

// ----------------------------------------------------------
// ICON LOGIC
// ----------------------------------------------------------
$topIcon = ""; // Shows beside client name

if (!$assignedCSR) {
    // Unassigned → show plus button
    $topIcon = "<button class='assign-btn' data-id='$clientID'>➕</button>";
} 
else if ($assignedCSR === $csrUser) {
    // Assigned to me → show minus (unassign)
    $topIcon = "<button class='unassign-btn' data-id='$clientID'>➖</button>";
} 
else {
    // Assigned to another CSR → show lock
    $topIcon = "<button class='locked-btn' data-id='$clientID'>🔒</button>";
}

// ----------------------------------------------------------
// TRANSFER REQUEST NOTICE
// ----------------------------------------------------------
$transferNotice = "";
if ($transferReq && $assignedCSR === $csrUser) {
    $transferNotice = "
        <div class='transfer-request-box'>
            <strong>$transferReq</strong> is requesting ownership of this client.<br><br>
            <button class='approve-transfer' data-id='$clientID'>Approve</button>
            <button class='deny-transfer' data-id='$clientID'>Deny</button>
        </div>
    ";
}

// ----------------------------------------------------------
// PENDING STATUS MESSAGE
// ----------------------------------------------------------
$pendingNote = "";
if ($ticketStatus === "pending") {
    $pendingNote = "
        <div class='pending-note'>
            🟡 This ticket is currently pending. CSR is coordinating with a field technician.
        </div>
    ";
}
?>

<div id="client-meta"
     data-ticket="<?= $ticketStatus ?>"
     data-assigned="<?= $assignedCSR ? 'yes' : 'no' ?>"
     data-assigned-csr="<?= htmlspecialchars($assignedCSR) ?>"
     data-transfer-request="<?= htmlspecialchars($transferReq) ?>"
     data-me="<?= htmlspecialchars($csrUser) ?>"
     data-locked="<?= $ticketLock ?>">
</div>

<h3 style='margin-bottom:6px; display:flex; align-items:center; gap:10px;'>
    <?= htmlspecialchars($client["full_name"]) ?>
    <?= $topIcon ?>
</h3>

<p><strong>Email:</strong> <?= htmlspecialchars($client["email"]) ?></p>
<p><strong>Account #:</strong> <?= htmlspecialchars($client["account_number"]) ?></p>
<p><strong>Ticket Status:</strong> 
    <span class="status-tag status-<?= $ticketStatus ?>">
        <?= strtoupper($ticketStatus) ?>
    </span>
</p>

<?= $pendingNote ?>
<?= $transferNotice ?>
