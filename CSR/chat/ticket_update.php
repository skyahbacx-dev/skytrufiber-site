<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

$clientID = $_POST["client_id"] ?? null;
$status   = $_POST["status"] ?? null;

$csrUser  = $_SESSION["csr_user"] ?? null;

if (!$clientID || !$status) {
    echo "Missing data";
    exit;
}

if (!$csrUser) {
    echo "Unauthorized";
    exit;
}

// ============================================================
// VERIFY CSR IS ASSIGNED TO THIS CLIENT
// ============================================================
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

$currentStatus = $info["ticket_status"];
$assignedCSR   = $info["assigned_csr"];

// Block update if this CSR is not assigned
if ($assignedCSR !== $csrUser) {
    echo "NOT_ASSIGNED";
    exit;
}

// ============================================================
// IF STATUS CHANGED → UPDATE & LOG
// ============================================================
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

    // LOG INTO ticket_logs (CORRECTED COLUMN NAMES)
    $log = $conn->prepare("
        INSERT INTO ticket_logs (client_id, new_status, changed_by, changed_at)
        VALUES (:cid, :st, :csr, NOW())
    ");
    $log->execute([
        ":cid" => $clientID,
        ":st"  => $status,   // 'resolved' or 'unresolved'
        ":csr" => $csrUser
    ]);
}

// ============================================================
// OPTIONAL AUTOMATIC UNLOCK WHEN RESOLVED
// ============================================================
// Remove this block if you do NOT want auto-unlock.
if ($status === "resolved") {
    $unlock = $conn->prepare("
        UPDATE users
        SET is_locked = FALSE,
            assigned_csr = NULL
        WHERE id = ?
    ");
    $unlock->execute([$clientID]);
}

echo "OK";
exit;
