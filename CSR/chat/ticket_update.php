<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$clientID = intval($_POST["client_id"] ?? 0);
$status   = strtolower($_POST["status"] ?? "");
$csrUser  = $_SESSION["csr_user"] ?? null;

if (!$clientID || !$status) {
    echo "Missing data";
    exit;
}

if (!$csrUser) {
    echo "Unauthorized";
    exit;
}

/* ============================================================
   VALID STATUS CHECK (unresolved, pending, resolved)
============================================================ */
$validStatuses = ["unresolved", "pending", "resolved"];
if (!in_array($status, $validStatuses)) {
    echo "INVALID_STATUS";
    exit;
}

/* ============================================================
   GET CURRENT ASSIGNMENT + STATUS
============================================================ */
$check = $conn->prepare("
    SELECT assigned_csr, ticket_status
    FROM users
    WHERE id = ?
    LIMIT 1
");
$check->execute([$clientID]);
$info = $check->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    echo "Client not found";
    exit;
}

$currentStatus = strtolower($info["ticket_status"]);
$assignedCSR   = $info["assigned_csr"];

/* ============================================================
   ONLY THE ASSIGNED CSR CAN CHANGE STATUS
============================================================ */
if ($assignedCSR !== $csrUser) {
    echo "NOT_ASSIGNED";
    exit;
}

/* ============================================================
   UPDATE STATUS IF CHANGED
============================================================ */
if ($currentStatus !== $status) {

    // Update users table
    $stmt = $conn->prepare("
        UPDATE users
        SET ticket_status = :s
        WHERE id = :id
    ");
    $stmt->execute([
        ":s" => $status,
        ":id" => $clientID
    ]);

    /* ============================================================
       LOG TO ticket_logs
       YOUR REAL STRUCTURE:
       (client_id, csr_user, action, timestamp)
    ============================================================ */
    
    $actionName = $status; // pending | resolved | unresolved

    $log = $conn->prepare("
        INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
        VALUES (:cid, :csr, :action, NOW())
    ");
    $log->execute([
        ":cid"    => $clientID,
        ":csr"    => $csrUser,
        ":action" => $actionName
    ]);
}

/* ============================================================
   OPTIONAL: AUTO-UNASSIGN + UNLOCK WHEN RESOLVED
============================================================ */
if ($status === "resolved") {

    // OPTIONAL — Remove assignment when ticket is done
    $unlock = $conn->prepare("
        UPDATE users
        SET assigned_csr = NULL,
            is_locked = FALSE,
            ticket_lock = 0,
            transfer_request = NULL
        WHERE id = ?
    ");
    $unlock->execute([$clientID]);
}

echo "OK";
exit;
?>
