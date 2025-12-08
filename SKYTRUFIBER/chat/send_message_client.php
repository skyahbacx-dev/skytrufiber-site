<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$ticketId = trim($_POST["ticket"] ?? "");
$message  = trim($_POST["message"] ?? "");

// ----------------------------------------------------------
// VALIDATE TICKET
// ----------------------------------------------------------
if (!$ticketId) {
    echo json_encode(["status" => "error", "msg" => "no ticket"]);
    exit;
}

// ----------------------------------------------------------
// FETCH TICKET & CLIENT
// ----------------------------------------------------------
$stmt = $conn->prepare("
    SELECT t.id AS ticket_id, t.status, t.client_id
    FROM tickets t
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo json_encode(["status" => "error", "msg" => "invalid ticket"]);
    exit;
}

$ticket_status = $ticket['status'] ?? 'unresolved';
$client_id = (int)$ticket['client_id'];

// ----------------------------------------------------------
// BLOCK MESSAGE IF TICKET IS RESOLVED
// ----------------------------------------------------------
if ($ticket_status === "resolved") {
    echo json_encode([
        "status" => "blocked",
        "msg" => "Ticket already resolved — messaging disabled."
    ]);
    exit;
}

// ----------------------------------------------------------
// PREVENT EMPTY MESSAGE
// ----------------------------------------------------------
if ($message === "") {
    echo json_encode(["status" => "ok", "msg" => "empty skipped"]);
    exit;
}

// ----------------------------------------------------------
// PREVENT DUPLICATE MESSAGE
// ----------------------------------------------------------
$check = $conn->prepare("
    SELECT 1
    FROM chat
    WHERE ticket_id = ?
      AND sender_type = 'client'
      AND message = ?
      AND deleted = FALSE
    LIMIT 1
");
$check->execute([$ticketId, $message]);
$exists = $check->fetchColumn();

if ($exists) {
    echo json_encode(["status" => "duplicate", "msg" => "message already exists"]);
    exit;
}

// ----------------------------------------------------------
// INSERT CLIENT MESSAGE
// ----------------------------------------------------------
// PostgreSQL booleans require TRUE/FALSE, not 1/0
$insert = $conn->prepare("
    INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, ?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$ticketId, $client_id, $message]);

echo json_encode(["status" => "ok"]);
exit;
?>
