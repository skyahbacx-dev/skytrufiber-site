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

// Block edits if CSR is not assigned
if ($assignedCSR !== $csrUser) {
    echo "NOT_ASSIGNED";
    exit;
}

// ============================================================
// UPDATE TICKET STATUS (only if changed)
// ============================================================
if ($currentStatus !== $status) {

    $stmt = $conn->prepare("
        UPDATE users 
        SET ticket_status = :s
        WHERE id = :id
    ");
    $stmt->execute([
        ":s" => $status,
        ":id" => $clientID
    ]);

    // ========================================================
    // LOG THE CHANGE in ticket_logs
    // ========================================================
    $log = $conn->prepare("
        INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
        VALUES (?, ?, ?, NOW())
    ");
    $log->execute([
        $clientID,
        $csrUser,
        $status  // action = 'resolved' or 'unresolved'
    ]);
}

// ============================================================
// OPTIONAL: Auto-Unlock Client When Resolved
// ============================================================
// Remove this feature by deleting this block
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
