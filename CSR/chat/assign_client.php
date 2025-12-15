<?php
if (!isset($_SESSION)) session_start();

/* Correct DB path for /CSR/chat/ files */
require __DIR__ . "/../../db_connect.php";

header("Content-Type: application/json");

$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);
$action   = trim($_POST["action"] ?? "");

if (!$csrUser) {
    echo json_encode(["status" => "error", "msg" => "CSR not logged in"]);
    exit;
}

if ($clientID <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid client"]);
    exit;
}

/* ============================================================
   FETCH CLIENT DATA
============================================================ */
$stmt = $conn->prepare("
    SELECT 
        assigned_csr, 
        ticket_lock, 
        ticket_status, 
        transfer_request
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

$assignedCSR  = $client["assigned_csr"];
$ticketLock   = intval($client["ticket_lock"]) === 1;
$ticketStatus = strtolower($client["ticket_status"]);
$transferReq  = $client["transfer_request"];

/* ============================================================
   A) ASSIGN CLIENT TO MYSELF
============================================================ */
if ($action === "assign") {

    // Already assigned to me
    if ($assignedCSR === $csrUser) {
        echo json_encode(["status" => "already", "msg" => "You already own this client."]);
        exit;
    }

    // Unassigned → assign immediately
    if (empty($assignedCSR)) {

        $update = $conn->prepare("
            UPDATE users
            SET assigned_csr = :csr,
                ticket_lock = 0,
                transfer_request = NULL
            WHERE id = :cid
        ");
        $update->execute([
            ":csr" => $csrUser,
            ":cid" => $clientID
        ]);

        // LOGGING
        $log = $conn->prepare("
            INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
            VALUES (:cid, :csr, 'assigned', NOW())
        ");
        $log->execute([
            ":cid" => $clientID,
            ":csr" => $csrUser
        ]);

        echo json_encode(["status" => "ok", "msg" => "Client successfully assigned."]);
        exit;
    }

    // Assigned to someone else → transfer required
    echo json_encode([
        "status" => "transfer_required",
        "msg"    => "Client belongs to {$assignedCSR} — request transfer?",
        "assigned_to" => $assignedCSR
    ]);
    exit;
}

/* ============================================================
   B) REQUEST TRANSFER FROM ANOTHER CSR
============================================================ */
if ($action === "request_transfer") {

    if ($assignedCSR === $csrUser) {
        echo json_encode(["status" => "already", "msg" => "You already own this client."]);
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
        "msg"    => "Transfer request sent to {$assignedCSR}."
    ]);
    exit;
}

/* ============================================================
   C) APPROVE TRANSFER (only current owner)
============================================================ */
if ($action === "approve_transfer") {

    if ($assignedCSR !== $csrUser) {
        echo json_encode(["status" => "denied", "msg" => "Only the assigned CSR can approve transfer."]);
        exit;
    }

    if (!$transferReq) {
        echo json_encode(["status" => "error", "msg" => "No transfer request available."]);
        exit;
    }

    // Transfer to requesting CSR
    $update = $conn->prepare("
        UPDATE users
        SET assigned_csr = :newcsr,
            ticket_lock = 0,
            transfer_request = NULL
        WHERE id = :cid
    ");
    $update->execute([
        ":newcsr" => $transferReq,
        ":cid"    => $clientID
    ]);

    // LOG
    $log = $conn->prepare("
        INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
        VALUES (:cid, :csr, :action, NOW())
    ");
    $log->execute([
        ":cid"   => $clientID,
        ":csr"   => $csrUser,
        ":action" => "transfer_approved_to_{$transferReq}"
    ]);

    echo json_encode([
        "status" => "ok",
        "msg"    => "Transfer approved. {$transferReq} now owns this client."
    ]);
    exit;
}

/* ============================================================
   D) DENY TRANSFER (only current owner)
============================================================ */
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

    echo json_encode(["status" => "ok", "msg" => "Transfer request denied."]);
    exit;
}

/* ============================================================
   E) UNASSIGN (only if assigned to me)
============================================================ */
if ($action === "unassign") {

    if ($assignedCSR !== $csrUser) {
        echo json_encode([
            "status" => "denied",
            "msg"    => "Cannot unassign — client owned by {$assignedCSR}"
        ]);
        exit;
    }

    $update = $conn->prepare("
        UPDATE users
        SET assigned_csr = NULL,
            ticket_lock = 0,
            transfer_request = NULL
        WHERE id = :cid
    ");
    $update->execute([":cid" => $clientID]);

    // LOG
    $log = $conn->prepare("
        INSERT INTO ticket_logs (client_id, csr_user, action, timestamp)
        VALUES (:cid, :csr, 'unassigned', NOW())
    ");
    $log->execute([
        ":cid" => $clientID,
        ":csr" => $csrUser
    ]);

    echo json_encode(["status" => "ok", "msg" => "You unassigned yourself."]);
    exit;
}

/* ============================================================
   INVALID ACTION
============================================================ */
echo json_encode(["status" => "error", "msg" => "Invalid action"]);
exit;

?>
