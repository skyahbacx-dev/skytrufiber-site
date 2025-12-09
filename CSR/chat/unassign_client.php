<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json");

$csrUser  = $_SESSION["csr_user"] ?? null;
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
// VALIDATION — Only assigned CSR can unassign
// ----------------------------------------------------------
if ($currentAssigned !== $csrUser) {
    echo json_encode([
        "status" => "denied",
        "msg"    => "You cannot unassign this client. Assigned to: $currentAssigned"
    ]);
    exit;
}

// ----------------------------------------------------------
// UNASSIGN + UNLOCK CLIENT
// ----------------------------------------------------------
$unassign = $conn->prepare("
    UPDATE users
    SET assigned_csr = NULL,
        ticket_lock = 0
    WHERE id = :cid
");
$unassign->execute([
    ":cid" => $clientID
]);

// ----------------------------------------------------------
// LOG THE UNASSIGNMENT
// ----------------------------------------------------------
$log = $conn->prepare("
    INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
    VALUES (:cid, :csr, 'unassigned', NOW())
");
$log->execute([
    ":cid" => $clientID,
    ":csr" => $csrUser
]);

echo json_encode([
    "status" => "ok",
    "msg"    => "Client unassigned and unlocked."
]);
exit;
?>
