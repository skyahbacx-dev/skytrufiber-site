<?php
/* ============================================================
   SESSION FIX — Prevent conflict with client session
============================================================ */
ini_set("session.name", "CSRSESSID");
if (!isset($_SESSION)) session_start();

header("Content-Type: application/json");

require "../../db_connect.php";

/* ============================================================
   VALIDATE SESSION
============================================================ */
$csrUser = $_SESSION["csr_user"] ?? null;

if (!$csrUser) {
    echo json_encode(["ok" => false, "msg" => "SESSION_EXPIRED"]);
    exit;
}

/* ============================================================
   INPUT VALIDATION
============================================================ */
$clientID = intval($_POST["client_id"] ?? 0);
$ticketID = intval($_POST["ticket_id"] ?? 0);
$status   = strtolower($_POST["status"] ?? "");

if (!$clientID || !$ticketID || !$status) {
    echo json_encode(["ok" => false, "msg" => "MISSING_DATA"]);
    exit;
}

$validStatuses = ["unresolved", "pending", "resolved"];
if (!in_array($status, $validStatuses, true)) {
    echo json_encode(["ok" => false, "msg" => "INVALID_STATUS"]);
    exit;
}

/* ============================================================
   FETCH TICKET + USER INFORMATION
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
    echo json_encode(["ok" => false, "msg" => "TICKET_NOT_FOUND"]);
    exit;
}

$currentStatus = strtolower($ticket["ticket_status"]);
$dbClientID    = (int)$ticket["client_id"];
$assignedCSR   = $ticket["assigned_csr"];

/* ============================================================
   SECURITY CHECKS
============================================================ */

// Ticket must match client
if ($dbClientID !== $clientID) {
    echo json_encode(["ok" => false, "msg" => "CLIENT_TICKET_MISMATCH"]);
    exit;
}

// CSR must be assigned OR reopening their own resolved ticket
if ($assignedCSR !== $csrUser && $currentStatus !== "resolved") {
    echo json_encode(["ok" => false, "msg" => "NOT_ASSIGNED"]);
    exit;
}

/* ============================================================
   UPDATE STATUS (ONLY IF CHANGED)
============================================================ */
if ($currentStatus !== $status) {

    // Update ticket status
    $update = $conn->prepare("
        UPDATE tickets
        SET status = :s
        WHERE id = :tid
    ");
    $update->execute([
        ":s"   => $status,
        ":tid" => $ticketID
    ]);

    // Log the change
    $log = $conn->prepare("
        INSERT INTO ticket_logs (ticket_id, client_id, csr_user, action, timestamp)
        VALUES (:tid, :cid, :csr, :action, NOW())
    ");
    $log->execute([
        ":tid"    => $ticketID,
        ":cid"    => $clientID,
        ":csr"    => $csrUser,
        ":action" => $currentStatus . "_to_" . $status
    ]);
}

/* ============================================================
   STATUS SIDE EFFECTS
============================================================ */

/* ---- RESOLVED ---- */
if ($status === "resolved") {

    $unlock = $conn->prepare("
        UPDATE users
        SET 
            assigned_csr = NULL,
            ticket_lock = FALSE,
            is_locked = FALSE,
            transfer_request = NULL
        WHERE id = ?
    ");
    $unlock->execute([$clientID]);
}

/* ---- REOPEN (FROM RESOLVED) ---- */
if ($currentStatus === "resolved" && $status !== "resolved") {

    $reassign = $conn->prepare("
        UPDATE users
        SET 
            assigned_csr = ?,
            ticket_lock = TRUE,
            is_locked = TRUE
        WHERE id = ?
    ");
    $reassign->execute([$csrUser, $clientID]);
}

/* ============================================================
   SUCCESS
============================================================ */
echo json_encode(["ok" => true]);
exit;
