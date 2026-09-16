<?php
// ------------------------------------------------------------
// CSR SESSION — prevents conflict with client login session
// ------------------------------------------------------------
ini_set("session.name", "CSRSESSID");
if (!isset($_SESSION)) session_start();

require __DIR__ . "/../../db_connect.php";
header("Content-Type: application/json");

// ------------------------------------------------------------
// INPUT VALIDATION
// ------------------------------------------------------------
$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);
$action   = trim($_POST["action"] ?? "");

if (!$csrUser) {
    echo json_encode(["status" => "error", "msg" => "Session expired"]);
    exit;
}

if ($clientID <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid client"]);
    exit;
}

/* ============================================================
   FETCH CLIENT + LATEST TICKET
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        u.assigned_csr,
        u.ticket_lock,
        u.transfer_request,

        /* Fetch latest ticket */
        (
            SELECT id FROM tickets 
            WHERE client_id = u.id 
            ORDER BY id DESC LIMIT 1
        ) AS ticket_id,

        (
            SELECT status FROM tickets 
            WHERE client_id = u.id 
            ORDER BY id DESC LIMIT 1
        ) AS ticket_status

    FROM users u
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$clientID]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    echo json_encode(["status" => "error", "msg" => "Client not found"]);
    exit;
}

$assignedCSR  = $client["assigned_csr"];
$isLocked     = intval($client["ticket_lock"]) === 1;
$transferReq  = $client["transfer_request"];
$ticketID     = intval($client["ticket_id"]);
$ticketStatus = strtolower($client["ticket_status"] ?? "unresolved");


// ============================================================
// AUTO-CREATE TICKET IF NONE EXISTS
// ============================================================
if (!$ticketID) {

    $new = $conn->prepare("
        INSERT INTO tickets (client_id, status, created_at)
        VALUES (:cid, 'unresolved', NOW())
    ");
    $new->execute([":cid" => $clientID]);

    $ticketID = $conn->lastInsertId();
    $ticketStatus = "unresolved";
}


/* ============================================================
   A) ASSIGN CLIENT TO MYSELF
============================================================ */
if ($action === "assign") {

    // Already mine
    if ($assignedCSR === $csrUser) {
        echo json_encode(["status" => "already", "msg" => "You already own this client."]);
        exit;
    }

    // Unassigned → take ownership
    if (empty($assignedCSR)) {

        $update = $conn->prepare("
            UPDATE users
            SET assigned_csr = :csr,
                ticket_lock = FALSE,
                transfer_request = NULL
            WHERE id = :cid
        ");
        $update->execute([
            ":csr" => $csrUser,
            ":cid" => $clientID
        ]);

        // Log
        $log = $conn->prepare("
            INSERT INTO ticket_logs (ticket_id, client_id, csr_user, action, timestamp)
            VALUES (:tid, :cid, :csr, 'assigned', NOW())
        ");
        $log->execute([
            ":tid" => $ticketID,
            ":cid" => $clientID,
            ":csr" => $csrUser
        ]);

        echo json_encode(["status" => "ok", "msg" => "Client assigned to you."]);
        exit;
    }

    // Owned by another CSR → transfer required
    echo json_encode([
        "status" => "transfer_required",
        "msg"    => "Client is currently assigned to {$assignedCSR}. Request transfer?",
        "assigned_to" => $assignedCSR
    ]);
    exit;
}


/* ============================================================
   B) REQUEST TRANSFER
============================================================ */
if ($action === "request_transfer") {

    if ($assignedCSR === $csrUser) {
        echo json_encode(["status" => "already", "msg" => "You already own this client."]);
        exit;
    }

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
        "msg"    => "Transfer request sent to {$assignedCSR}."
    ]);
    exit;
}


/* ============================================================
   C) APPROVE TRANSFER (Only owner)
============================================================ */
if ($action === "approve_transfer") {

    if ($assignedCSR !== $csrUser) {
        echo json_encode(["status" => "denied", "msg" => "Only assigned CSR may approve transfer."]);
        exit;
    }

    if (!$transferReq) {
        echo json_encode(["status" => "error", "msg" => "No transfer request recorded."]);
        exit;
    }

    // Update assignment
    $update = $conn->prepare("
        UPDATE users
        SET assigned_csr = :newcsr,
            ticket_lock = FALSE,
            transfer_request = NULL
        WHERE id = :cid
    ");
    $update->execute([
        ":newcsr" => $transferReq,
        ":cid"    => $clientID
    ]);

    // Log
    $log = $conn->prepare("
        INSERT INTO ticket_logs (ticket_id, client_id, csr_user, action, timestamp)
        VALUES (:tid, :cid, :csr, :action, NOW())
    ");
    $log->execute([
        ":tid"    => $ticketID,
        ":cid"    => $clientID,
        ":csr"    => $csrUser,
        ":action" => "transfer_approved_to_{$transferReq}"
    ]);

    echo json_encode([
        "status" => "ok",
        "msg"    => "Transfer approved. {$transferReq} is now the assigned CSR."
    ]);
    exit;
}


/* ============================================================
   D) DENY TRANSFER
============================================================ */
if ($action === "deny_transfer") {

    if ($assignedCSR !== $csrUser) {
        echo json_encode(["status" => "denied", "msg" => "Only assigned CSR may deny transfer."]);
        exit;
    }

    $update = $conn->prepare("
        UPDATE users
        SET transfer_request = NULL
        WHERE id = :cid
    ");
    $update->execute([":cid" => $clientID]);

    echo json_encode(["status" => "ok", "msg" => "Transfer request denied."]);
    exit;
}


/* ============================================================
   E) UNASSIGN — Only if assigned to me
============================================================ */
if ($action === "unassign") {

    if ($assignedCSR !== $csrUser) {
        echo json_encode([
            "status" => "denied",
            "msg"    => "Cannot unassign. Client is owned by {$assignedCSR}."
        ]);
        exit;
    }

    $update = $conn->prepare("
        UPDATE users
        SET assigned_csr = NULL,
            ticket_lock = FALSE,
            transfer_request = NULL
        WHERE id = :cid
    ");
    $update->execute([":cid" => $clientID]);

    // Log
    $log = $conn->prepare("
        INSERT INTO ticket_logs (ticket_id, client_id, csr_user, action, timestamp)
        VALUES (:tid, :cid, :csr, 'unassigned', NOW())
    ");
    $log->execute([
        ":tid" => $ticketID,
        ":cid" => $clientID,
        ":csr" => $csrUser
    ]);

    echo json_encode(["status" => "ok", "msg" => "Client unassigned."]);
    exit;
}


/* ============================================================
   INVALID ACTION
============================================================ */
echo json_encode(["status" => "error", "msg" => "Invalid action"]);
exit;

?>
