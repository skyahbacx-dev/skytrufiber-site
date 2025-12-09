<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json");

$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);
$state    = trim($_POST["state"] ?? ""); // "lock" or "unlock"

if (!$csrUser) {
    echo json_encode(["status" => "error", "msg" => "CSR not logged in"]);
    exit;
}

if ($clientID <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid client"]);
    exit;
}

// ----------------------------------------------------------
// GET CLIENT INFORMATION
// ----------------------------------------------------------
$stmt = $conn->prepare("
    SELECT assigned_csr, ticket_lock
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$clientID]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    echo json_encode(["status" => "error", "msg" => "Client not found"]);
    exit;
}

$currentAssigned = $c["assigned_csr"];
$isLocked        = $c["ticket_lock"] == 1;

// ----------------------------------------------------------
// Only ASSIGNED CSR can lock/unlock
// ----------------------------------------------------------
if ($currentAssigned !== $csrUser) {
    echo json_encode([
        "status" => "denied",
        "msg"    => "Only the assigned CSR can lock/unlock this client."
    ]);
    exit;
}

// ----------------------------------------------------------
// PROCESS LOCK
// ----------------------------------------------------------
if ($state === "lock") {

    $update = $conn->prepare("
        UPDATE users
        SET ticket_lock = 1,
            ticket_status = 'pending'
        WHERE id = :cid
    ");
    $update->execute([":cid" => $clientID]);

    // Log action
    $log = $conn->prepare("
        INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
        VALUES (:cid, :csr, 'locked', NOW())
    ");
    $log->execute([
        ":cid" => $clientID,
        ":csr" => $csrUser
    ]);

    echo json_encode([
        "status" => "ok",
        "msg"    => "Client locked and marked as PENDING."
    ]);
    exit;
}

// ----------------------------------------------------------
// PROCESS UNLOCK
// ----------------------------------------------------------
if ($state === "unlock") {

    $update = $conn->prepare("
        UPDATE users
        SET ticket_lock = 0,
            ticket_status = 'unresolved'
        WHERE id = :cid
    ");
    $update->execute([":cid" => $clientID]);

    // Log action
    $log = $conn->prepare("
        INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
        VALUES (:cid, :csr, 'unlocked', NOW())
    ");
    $log->execute([
        ":cid" => $clientID,
        ":csr" => $csrUser
    ]);

    echo json_encode([
        "status" => "ok",
        "msg"    => "Client unlocked and returned to UNRESOLVED."
    ]);
    exit;
}

echo json_encode(["status" => "error", "msg" => "Invalid lock state."]);
exit;
?>
