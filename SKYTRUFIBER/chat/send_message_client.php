<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$ticketId = (int)($_POST["ticket"] ?? 0);
$message  = trim($_POST["message"] ?? "");

// ----------------------------------------------------------
// VALIDATION
// ----------------------------------------------------------
if ($ticketId <= 0) {
    echo json_encode(["status" => "error", "msg" => "invalid ticket"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT client_id, status
    FROM tickets
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo json_encode(["status" => "error", "msg" => "ticket not found"]);
    exit;
}

if ($ticket["status"] === "resolved") {
    echo json_encode(["status" => "blocked"]);
    exit;
}

$clientId = (int)$ticket["client_id"];

// ----------------------------------------------------------
// BLOCK EMPTY MESSAGE
// ----------------------------------------------------------
if ($message === "") {
    echo json_encode(["status" => "empty"]);
    exit;
}

// ----------------------------------------------------------
// CHECK IF THIS IS THE FIRST CLIENT MESSAGE
//
// IMPORTANT:
//   CSR greeting already exists (login inserted it)
//   So we ONLY check for *client* messages.
// ----------------------------------------------------------
$check = $conn->prepare("
    SELECT COUNT(*) 
    FROM chat 
    WHERE ticket_id = ? 
      AND sender_type = 'client'
");
$check->execute([$ticketId]);
$existingClientMessages = (int)$check->fetchColumn();

$isFirstClientMessage = ($existingClientMessages === 0);

// ----------------------------------------------------------
// INSERT CLIENT MESSAGE
// ----------------------------------------------------------
$insert = $conn->prepare("
    INSERT INTO chat (
        ticket_id, client_id, sender_type, message,
        delivered, seen, created_at
    ) VALUES (?, ?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$ticketId, $clientId, $message]);

// ----------------------------------------------------------
// RETURN RESPONSE FOR JS
// Trigger suggestion bubble *ONLY* on FIRST client message.
// ----------------------------------------------------------
if ($isFirstClientMessage) {
    echo json_encode([
        "status" => "ok",
        "first_message" => true
    ]);
    exit;
}

echo json_encode(["status" => "ok"]);
exit;

?>
