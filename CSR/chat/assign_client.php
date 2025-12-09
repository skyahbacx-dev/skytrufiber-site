<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json");

$csrUser = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);

if (!$csrUser) {
    echo json_encode(["status" => "error", "msg" => "CSR not logged in"]);
    exit;
}

if ($clientID <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid client"]);
    exit;
}

// ----------------------------------------------------------
// FETCH CLIENT INFO
// ----------------------------------------------------------
$stmt = $conn->prepare("
    SELECT assigned_csr, ticket_status, ticket_lock
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
$isLocked       = $c["ticket_lock"] == 1;

// ----------------------------------------------------------
// Client is already assigned — cannot steal assignment
// ----------------------------------------------------------
if (!empty($currentAssigned) && $currentAssigned !== $csrUser) {
    echo json_encode([
        "status" => "denied",
        "msg"    => "Client is already assigned to another CSR: $currentAssigned"
    ]);
    exit;
}

// ----------------------------------------------------------
// ASSIGN CLIENT TO THIS CSR
// ----------------------------------------------------------
$assign = $conn->prepare("
    UPDATE users
    SET assigned_csr = :csr,
        ticket_lock  = 1,          -- LOCK for all other CSRs
        ticket_status = 'unresolved'
    WHERE id = :cid
");
$assign->execute([
    ":csr" => $csrUser,
    ":cid" => $clientID
]);

// ----------------------------------------------------------
// LOG THE ASSIGNMENT
// ----------------------------------------------------------
$log = $conn->prepare("
    INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
    VALUES (:cid, :csr, 'assigned', NOW())
");
$log->execute([
    ":cid" => $clientID,
    ":csr" => $csrUser
]);

echo json_encode([
    "status" => "ok",
    "msg"    => "Client successfully assigned to you",
    "assigned_to" => $csrUser
]);
exit;
?>
