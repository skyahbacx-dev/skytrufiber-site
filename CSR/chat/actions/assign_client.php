<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json");

$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);
$action   = trim($_POST["action"] ?? ""); 
// actions = assign, unassign, request_transfer, approve_transfer, deny_transfer

if (!$csrUser) {
    echo json_encode(["status" => "error", "msg" => "CSR not logged in"]);
    exit;
}

if ($clientID <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid client"]);
    exit;
}

// ----------------------------------------------------------
// FETCH CLIENT CURRENT ASSIGNMENT
// ----------------------------------------------------------
$stmt = $conn->prepare("
    SELECT assigned_csr, ticket_lock, ticket_status, transfer_request
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$clientID]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    echo json_encode(["status" => "error", "msg" => "Client not found"]);
    exit;
}

$assignedCSR     = $client["assigned_csr"];
$ticketLock       = $client["ticket_lock"];
$ticketStatus     = $client["ticket_status"];
$transferRequest  = $client["transfer_request"]; // NEW column

// ----------------------------------------------------------
// CASE 1 — ASSIGN TO MYSELF (client has no CSR)
// ----------------------------------------------------------
if ($action === "assign") {

    if ($assignedCSR === $csrUser) {
        echo json_encode(["status" => "already", "msg" => "You already own this ticket."]);
        exit;
    }

    if ($assignedCSR === null) {

        // Assign directly
        $update = $conn->prepare("
            UPDATE users
            SET assigned_csr = :csr
            WHERE id = :cid
        ");
        $update->execute([
            ":csr" => $csrUser,
            ":cid" => $clientID
        ]);

        // Log assignment
        $log = $conn->prepare("
            INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
            VALUES (:cid, :csr, 'assigned', NOW())
        ");
        $log->execute([":cid" => $clientID, ":csr" => $csrUser]);

        echo json_encode(["status" => "ok", "msg" => "Client assigned to you."]);
        exit;
    }

    // Assigned to someone else → TRANSFER REQUEST
    echo json_encode([
        "status" => "transfer_required",
        "msg"    => "This client is assigned to another CSR. Request transfer?",
        "assigned_to" => $assignedCSR
    ]);
    exit;
}

// ----------------------------------------------------------
// CASE 2 — REQUEST TRANSFER
// ----------------------------------------------------------
if ($action === "request_transfer") {

    if ($assignedCSR === $csrUser) {
        echo json_encode(["status" => "already", "msg" => "You already own this ticket."]);
        exit;
    }

    // Write transfer request
    $update = $conn->prepare("
        UPDATE users
        SET transfer_request = :csr
        WHERE id = :cid
    ");
    $update->execute([
        ":csr" => $csrUser,
        ":cid" => $clientID
    ]);

    echo json_encode([
        "status" => "requested",
        "msg"    => "Transfer request sent to $assignedCSR."
    ]);
    exit;
}

// ----------------------------------------------------------
// CASE 3 — CURRENT CSR APPROVES TRANSFER
// ----------------------------------------------------------
if ($action === "approve_transfer") {

    if ($assignedCSR !== $csrUser) {
        echo json_encode(["status" => "denied", "msg" => "Only the assigned CSR can approve transfer."]);
        exit;
    }

    if (!$transferRequest) {
        echo json_encode(["status" => "error", "msg" => "No transfer request exists."]);
        exit;
    }

    // Approve transfer
    $update = $conn->prepare("
        UPDATE users
        SET assigned_csr = :newcsr,
            transfer_request = NULL
        WHERE id = :cid
    ");
    $update->execute([
        ":newcsr" => $transferRequest,
        ":cid"    => $clientID
    ]);

    // Log
    $log = $conn->prepare("
        INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
        VALUES (:cid, :csr, 'transfer_approved_to_" . $transferRequest . "', NOW())
    ");
    $log->execute([
        ":cid" => $clientID,
        ":csr" => $csrUser
    ]);

    echo json_encode([
        "status" => "ok",
        "msg"    => "Transfer approved. $transferRequest now owns this ticket."
    ]);
    exit;
}

// ----------------------------------------------------------
// CASE 4 — CURRENT CSR DENIES TRANSFER
// ----------------------------------------------------------
if ($action === "deny_transfer") {

    if ($assignedCSR !== $csrUser) {
        echo json_encode(["status" => "denied", "msg" => "Only the assigned CSR can deny transfer."]);
        exit;
    }

    $update = $conn->prepare("
        UPDATE users
        SET transfer_request = NULL
        WHERE id = :cid
    ");
    $update->execute([":cid" => $clientID]);

    echo json_encode([
        "status" => "ok",
        "msg"    => "Transfer request denied."
    ]);
    exit;
}

// ----------------------------------------------------------
// CASE 5 — UNASSIGN MYSELF
// ----------------------------------------------------------
if ($action === "unassign") {

    if ($assignedCSR !== $csrUser) {
        echo json_encode(["status" => "denied", "msg" => "Only the current CSR can unassign."]);
        exit;
    }

    $update = $conn->prepare("
        UPDATE users
        SET assigned_csr = NULL,
            ticket_lock = 0,
            transfer_request = NULL,
            ticket_status = 'unresolved'
        WHERE id = :cid
    ");
    $update->execute([":cid" => $clientID]);

    $log = $conn->prepare("
        INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
        VALUES (:cid, :csr, 'unassigned', NOW())
    ");
    $log->execute([":cid" => $clientID, ":csr" => $csrUser]);

    echo json_encode([
        "status" => "ok",
        "msg"    => "You unassigned yourself."
    ]);
    exit;
}

echo json_encode(["status" => "error", "msg" => "Invalid action"]);
exit;
?>
