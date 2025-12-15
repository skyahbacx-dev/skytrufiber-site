<?php
ini_set("session.name", "CSRSESSID");
session_start();

require "../../db_connect.php";

header("Content-Type: application/json");

// ------------------------------
// INPUT VALIDATION
// ------------------------------
$clientID = intval($_POST["client_id"] ?? 0);
$ticketID = intval($_POST["ticket_id"] ?? 0);
$status   = strtolower($_POST["status"] ?? "");
$csrUser  = $_SESSION["csr_user"] ?? null;

if (!$csrUser) {
    echo json_encode(["ok" => false, "msg" => "UNAUTHORIZED"]);
    exit;
}

if (!$clientID || !$ticketID || !$status) {
    echo json_encode(["ok" => false, "msg" => "MISSING_DATA"]);
    exit;
}

// Allowed statuses
$validStatuses = ["unresolved", "pending", "resolved"];
if (!in_array($status, $validStatuses)) {
    echo json_encode(["ok" => false, "msg" => "INVALID_STATUS"]);
    exit;
}

// ------------------------------
// FETCH TICKET + USER META
// ------------------------------
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

if (!$ticket) {
    echo json_encode(["ok" => false, "msg" => "TICKET_NOT_FOUND"]);
    exit;
}

$currentStatus = strtolower($ticket["ticket_status"]);
$dbClientID    = intval($ticket["client_id"]);
$assignedCSR   = $ticket["assigned_csr"];

// ------------------------------
// SECURITY CHECKS
// ------------------------------

// 1. Ticket must belong to the client
if ($dbClientID !== $clientID) {
    echo json_encode(["ok" => false, "msg" => "CLIENT_TICKET_MISMATCH"]);
    exit;
}

// 2. CSR must be assigned to this client
if ($assignedCSR !== $csrUser) {
    echo json_encode(["ok" => false, "msg" => "NOT_ASSIGNED"]);
    exit;
}

// 3. Cannot update already resolved ticket
if ($currentStatus === "resolved") {
    echo json_encode(["ok" => false, "msg" => "ALREADY_RESOLVED"]);
    exit;
}

// ------------------------------
// UPDATE STATUS (ONLY IF NEW STATUS)
// ------------------------------
if ($currentStatus !== $status) {

    // Update ticket status
    $update = $conn->prepare("
        UPDATE tickets 
        SET status = :s 
        WHERE id = :tid
    ");
    $update->execute([
        ":s" => $status,
        ":tid" => $ticketID
    ]);

    // Insert log
    $log = $conn->prepare("
        INSERT INTO ticket_logs (ticket_id, client_id, csr_user, action, timestamp)
        VALUES (:tid, :cid, :csr, :action, NOW())
    ");
    $log->execute([
        ":tid" => $ticketID,
        ":cid" => $clientID,
        ":csr" => $csrUser,
        ":action" => $status
    ]);
}

// ------------------------------
// IF RESOLVED → UNLOCK USER
// ------------------------------
if ($status === "resolved") {
    $unlock = $conn->prepare("
        UPDATE users
        SET 
            ticket_lock = FALSE,
            is_locked = FALSE,
            transfer_request = NULL
        WHERE id = ?
    ");
    $unlock->execute([$clientID]);
}

// ------------------------------
echo json_encode(["ok" => true]);
exit;
?>
