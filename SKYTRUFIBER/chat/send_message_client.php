<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

/* ----------------------------------------------------------
   INPUTS
---------------------------------------------------------- */
$ticketId = (int)($_POST["ticket"] ?? 0);
$message  = trim($_POST["message"] ?? "");

/* ----------------------------------------------------------
   VALIDATE TICKET ID
---------------------------------------------------------- */
if ($ticketId <= 0) {
    echo json_encode(["status" => "error", "msg" => "invalid_ticket"]);
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
    echo json_encode(["status" => "error", "msg" => "ticket_not_found"]);
    exit;
}

$clientId = (int)$ticket["client_id"];

/* ----------------------------------------------------------
   SECURITY CHECK:
   Ensure session client is the SAME owner of this ticket
---------------------------------------------------------- */
if (!isset($_SESSION["client_id"]) || $_SESSION["client_id"] != $clientId) {
    echo json_encode(["status" => "error", "msg" => "not_authorized"]);
    exit;
}

/* ----------------------------------------------------------
   IF TICKET IS RESOLVED → BLOCK MESSAGE & FORCE LOGOUT
---------------------------------------------------------- */
if ($ticket["status"] === "resolved") {

    // Mark for logout on next poll so UI resets properly
    $_SESSION["force_logout"] = true;

    echo json_encode([
        "status" => "blocked",
        "msg" => "ticket_resolved"
    ]);
    exit;
}

/* ----------------------------------------------------------
   PREVENT EMPTY MESSAGES
---------------------------------------------------------- */
if ($message === "" || strlen(trim($message)) === 0) {
    echo json_encode(["status" => "empty"]);
    exit;
}

/* ----------------------------------------------------------
   DETECT IF THIS IS THE FIRST CLIENT MESSAGE
   (CSR greeting exists already)
---------------------------------------------------------- */
$check = $conn->prepare("
    SELECT COUNT(*)
    FROM chat
    WHERE ticket_id = ?
      AND sender_type = 'client'
");
$check->execute([$ticketId]);
$existingClientCount = (int)$check->fetchColumn();

$isFirstClientMessage = ($existingClientCount === 0);

/* ----------------------------------------------------------
   INSERT MESSAGE
---------------------------------------------------------- */
$insert = $conn->prepare("
    INSERT INTO chat (
        ticket_id,
        client_id,
        sender_type,
        message,
        delivered,
        seen,
        created_at
    ) VALUES (?, ?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$ticketId, $clientId, $message]);

/* ----------------------------------------------------------
   FIRST MESSAGE → TRIGGER SUGGESTIONS
---------------------------------------------------------- */
if ($isFirstClientMessage) {

    $_SESSION["show_suggestions"] = true;

    echo json_encode([
        "status" => "ok",
        "first_message" => true
    ]);
    exit;
}

/* ----------------------------------------------------------
   NORMAL SUCCESS RESPONSE
---------------------------------------------------------- */
echo json_encode(["status" => "ok"]);
exit;
?>
