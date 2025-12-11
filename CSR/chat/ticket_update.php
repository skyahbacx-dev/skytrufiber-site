<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$clientID = intval($_POST["client_id"] ?? 0);
$ticketID = intval($_POST["ticket_id"] ?? 0);
$status   = strtolower($_POST["status"] ?? "");
$csrUser  = $_SESSION["csr_user"] ?? null;

if (!$clientID || !$ticketID || !$status) {
    echo "MISSING_DATA";
    exit;
}

if (!$csrUser) {
    echo "UNAUTHORIZED";
    exit;
}

/* ============================================================
   VALID STATUS CHECK
============================================================ */
$validStatuses = ["unresolved", "pending", "resolved"];
if (!in_array($status, $validStatuses)) {
    echo "INVALID_STATUS";
    exit;
}

/* ============================================================
   FETCH ACTIVE TICKET INFO
============================================================ */
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
    echo "TICKET_NOT_FOUND";
    exit;
}

$currentStatus = strtolower($ticket["ticket_status"]);
$assignedCSR   = $ticket["assigned_csr"];
$dbClientID    = intval($ticket["client_id"]);

/* ============================================================
   VERIFY TICKET BELONGS TO CLIENT
============================================================ */
if ($dbClientID !== $clientID) {
    echo "CLIENT_TICKET_MISMATCH";
    exit;
}

/* ============================================================
   ONLY ASSIGNED CSR CAN CHANGE STATUS
============================================================ */
if ($assignedCSR !== $csrUser) {
    echo "NOT_ASSIGNED";
    exit;
}

/* ============================================================
   PREVENT UPDATES TO ALREADY RESOLVED TICKETS
============================================================ */
if ($currentStatus === "resolved") {
    echo "ALREADY_RESOLVED";
    exit;
}

/* ============================================================
   UPDATE TICKET STATUS
============================================================ */
if ($currentStatus !== $status) {

    $update = $conn->prepare("
        UPDATE tickets
        SET status = :s
        WHERE id = :tid
    ");
    $update->execute([
        ":s"   => $status,
        ":tid" => $ticketID
    ]);

    /* ============================================================
       INSERT TICKET LOG ENTRY
============================================================ */
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

/* ============================================================
   IF RESOLVED → UNASSIGN & UNLOCK CLIENT
============================================================ */
if ($status === "resolved") {

    $unlock = $conn->prepare("
        UPDATE users
        SET assigned_csr = NULL,
            is_locked = FALSE,
            ticket_lock = FALSE,
            transfer_request = NULL
        WHERE id = ?
    ");
    $unlock->execute([$clientID]);
}

echo "OK";
exit;
?>
