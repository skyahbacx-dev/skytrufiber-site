<?php
/* ============================================================
   SESSION FIX — Prevent conflict with client sessions
============================================================ */
ini_set("session.name", "CSRSESSID");
if (!isset($_SESSION)) session_start();

header("Content-Type: application/json; charset=utf-8");

require "../../db_connect.php";

/* ============================================================
   VALIDATE SESSION
============================================================ */
$csrUser = $_SESSION["csr_user"] ?? null;

if (!$csrUser) {
    echo json_encode(["status" => "SESSION_EXPIRED"]);
    exit;
}

/* ============================================================
   INPUT VALIDATION
============================================================ */
$clientID = intval($_POST["client_id"] ?? 0);
$ticketID = intval($_POST["ticket_id"] ?? 0);
$message  = trim($_POST["message"] ?? "");

if ($clientID <= 0 || $ticketID <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid client or ticket."]);
    exit;
}

if ($message === "") {
    echo json_encode(["status" => "error", "msg" => "Message cannot be empty."]);
    exit;
}

try {

    /* ============================================================
       1) VALIDATE TICKET + USER MATCH
    ============================================================= */
    $stmt = $conn->prepare("
        SELECT 
            t.status AS ticket_status,
            t.client_id,
            u.assigned_csr
        FROM tickets t
        JOIN users u ON u.id = t.client_id
        WHERE t.id = :tid
        LIMIT 1
    ");
    $stmt->execute([":tid" => $ticketID]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo json_encode(["status" => "error", "msg" => "Ticket not found."]);
        exit;
    }

    $ticketStatus = strtolower($ticket["ticket_status"]);
    $assignedCSR  = $ticket["assigned_csr"];
    $dbClientID   = intval($ticket["client_id"]);

    if ($dbClientID !== $clientID) {
        echo json_encode(["status" => "error", "msg" => "Ticket does not belong to this client."]);
        exit;
    }

    /* ============================================================
       2) BLOCK IF TICKET IS RESOLVED
    ============================================================= */
    if ($ticketStatus === "resolved") {
        echo json_encode([
            "status" => "blocked",
            "msg"    => "This ticket is already resolved. Messaging disabled."
        ]);
        exit;
    }

    /* ============================================================
       3) BLOCK IF CSR IS NOT ASSIGNED TO THIS CLIENT
    ============================================================= */
    if ($assignedCSR !== $csrUser) {
        echo json_encode([
            "status" => "locked",
            "msg"    => "You are not assigned to this ticket."
        ]);
        exit;
    }

    /* ============================================================
       4) INSERT CSR MESSAGE
    ============================================================= */
    $insert = $conn->prepare("
        INSERT INTO chat (
            ticket_id,
            client_id,
            sender_type,
            message,
            deleted,
            edited,
            delivered,
            seen,
            created_at
        ) VALUES (
            :tid,
            :cid,
            'csr',
            :msg,
            FALSE,
            FALSE,
            TRUE,
            FALSE,
            NOW()
        )
    ");

    $insert->execute([
        ":tid" => $ticketID,
        ":cid" => $clientID,
        ":msg" => $message
    ]);

    echo json_encode([
        "status" => "ok",
        "msg"    => "Message sent"
    ]);
    exit;


} catch (Throwable $e) {

    echo json_encode([
        "status" => "error",
        "msg"    => "Server error: " . $e->getMessage()
    ]);
    exit;
}
?>
