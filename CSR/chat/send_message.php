<?php
if (!isset($_SESSION)) session_start();
require "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

$csrUser  = $_SESSION["csr_user"] ?? null;
$clientID = intval($_POST["client_id"] ?? 0);
$ticketID = intval($_POST["ticket_id"] ?? 0);
$message  = trim($_POST["message"] ?? "");

if (!$csrUser) {
    echo json_encode(["status" => "error", "msg" => "CSR not logged in"]);
    exit;
}

if ($clientID <= 0 || $ticketID <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid client or ticket"]);
    exit;
}

if ($message === "") {
    echo json_encode(["status" => "error", "msg" => "Message cannot be empty"]);
    exit;
}

try {

    /* ==========================================================
       1) Validate ticket belongs to client & assignment
    ========================================================== */
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
        echo json_encode(["status" => "error", "msg" => "Ticket not found"]);
        exit;
    }

    $ticketStatus = strtolower($ticket["ticket_status"]);
    $assignedCSR  = $ticket["assigned_csr"];
    $dbClientID   = intval($ticket["client_id"]);

    /* Ensure correct client–ticket mapping */
    if ($dbClientID !== $clientID) {
        echo json_encode(["status" => "error", "msg" => "Ticket does not belong to this client"]);
        exit;
    }

    /* ==========================================================
       2) Block messaging in resolved tickets
    ========================================================== */
    if ($ticketStatus === "resolved") {
        echo json_encode([
            "status" => "blocked",
            "msg"    => "This ticket is already resolved. Messaging disabled."
        ]);
        exit;
    }

    /* ==========================================================
       3) CSR assignment enforcement
    ========================================================== */
    if ($assignedCSR !== $csrUser) {
        echo json_encode([
            "status" => "locked",
            "msg"    => "You are not assigned to this ticket."
        ]);
        exit;
    }

    /* ==========================================================
       4) Insert CSR message (linked to ticket_id)
    ========================================================== */
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
            :tid, :cid, 'csr', :msg, FALSE, FALSE, TRUE, FALSE, NOW()
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
