<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// -------------------------------------
// INPUT VALIDATION
// -------------------------------------
$clientID = intval($_POST["client_id"] ?? 0);
$ticketID = intval($_POST["ticket_id"] ?? 0);
$status   = strtolower($_POST["status"] ?? "");
$csrUser  = $_SESSION["csr_user"] ?? null;

if (!$csrUser)             exit(json_encode(["ok"=>false,"msg"=>"UNAUTHORIZED"]));
if (!$clientID || !$ticketID || !$status)
    exit(json_encode(["ok"=>false,"msg"=>"MISSING_DATA"]));

$validStatuses = ["unresolved", "pending", "resolved"];
if (!in_array($status, $validStatuses))
    exit(json_encode(["ok"=>false,"msg"=>"INVALID_STATUS"]));

// -------------------------------------
// FETCH TICKET + USER
// -------------------------------------
$stmt = $conn->prepare("
    SELECT 
        t.status AS ticket_status,
        t.client_id,
        u.assigned_csr
    FROM tickets t
    JOIN users u ON u.id = t.client_id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticketID]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket)
    exit(json_encode(["ok"=>false,"msg"=>"TICKET_NOT_FOUND"]));

$currentStatus = strtolower($ticket["ticket_status"]);
$assignedCSR   = $ticket["assigned_csr"];
$dbClientID    = intval($ticket["client_id"]);

// Client mismatch protection
if ($dbClientID !== $clientID)
    exit(json_encode(["ok"=>false,"msg"=>"CLIENT_TICKET_MISMATCH"]));

// CSR assignment security
if ($assignedCSR !== $csrUser)
    exit(json_encode(["ok"=>false,"msg"=>"NOT_ASSIGNED"]));

// Prevent modifying resolved ticket
if ($currentStatus === "resolved" && $status !== "resolved")
    exit(json_encode(["ok"=>false,"msg"=>"ALREADY_RESOLVED"]));


// -------------------------------------
// APPLY STATUS UPDATE (ONLY IF CHANGED)
// -------------------------------------
if ($currentStatus !== $status) {

    $update = $conn->prepare("
        UPDATE tickets 
        SET status = :s, updated_at = NOW()
        WHERE id = :tid
    ");
    $update->execute([":s"=>$status, ":tid"=>$ticketID]);

    // Log the change
    $log = $conn->prepare("
        INSERT INTO ticket_logs (ticket_id, client_id, csr_user, action, timestamp)
        VALUES (:tid, :cid, :csr, :action, NOW())
    ");
    $log->execute([
        ":tid"    => $ticketID,
        ":cid"    => $clientID,
        ":csr"    => $csrUser,
        ":action" => $status
    ]);
}


// -------------------------------------
// SPECIAL RULES WHEN RESOLVING
// -------------------------------------
if ($status === "resolved") {

    // Unassign & unlock immediately
    $unlock = $conn->prepare("
        UPDATE users
        SET assigned_csr     = NULL,
            ticket_lock      = FALSE,
            transfer_request = NULL
        WHERE id = ?
    ");
    $unlock->execute([$clientID]);
}

// -------------------------------------
// SPECIAL RULE: unresolved & pending re-enable chat
// -------------------------------------
if (in_array($status, ["unresolved", "pending"])) {

    $unlock = $conn->prepare("
        UPDATE users
        SET ticket_lock = FALSE
        WHERE id = ?
    ");
    $unlock->execute([$clientID]);
}


// -------------------------------------
// RETURN UPDATED STATUS IMMEDIATELY
// Allows instant UI update with zero delay
// -------------------------------------
echo json_encode([
    "ok"     => true,
    "ticket" => $ticketID,
    "status" => $status,
    "client" => $clientID
]);

exit;
?>
