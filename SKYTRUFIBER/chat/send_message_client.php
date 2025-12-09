<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$ticketId = (int)($_POST["ticket"] ?? 0);
$message  = trim($_POST["message"] ?? "");

/* ----------------------------------------------------------
   BASIC VALIDATION
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

if ($ticket["status"] === "resolved") {
    echo json_encode(["status" => "blocked", "msg" => "ticket_resolved"]);
    exit;
}

$clientId = (int)$ticket["client_id"];

/* ----------------------------------------------------------
   PREVENT EMPTY / BLANK MESSAGES
---------------------------------------------------------- */
if ($message === "" || strlen(trim($message)) === 0) {
    echo json_encode(["status" => "empty"]);
    exit;
}

/* ----------------------------------------------------------
   DETECT IF THIS IS THE FIRST CLIENT MESSAGE
   (CSR greeting is already created during login)
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
   INSERT CLIENT MESSAGE INTO DATABASE
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
    ) VALUES (
        ?, ?, 'client', ?, TRUE, FALSE, NOW()
    )
");
$insert->execute([$ticketId, $clientId, $message]);

/* ----------------------------------------------------------
   FIRST CLIENT MESSAGE → TRIGGER SUGGESTION FLAG
---------------------------------------------------------- */
if ($isFirstClientMessage) {
    // Set FLAG so load_messages_client.php will insert suggestions
    $_SESSION["show_suggestions"] = true;

    echo json_encode([
        "status" => "ok",
        "first_message" => true
    ]);
    exit;
}

/* ----------------------------------------------------------
   NORMAL CLIENT MESSAGE RESPONSE
---------------------------------------------------------- */
echo json_encode(["status" => "ok"]);
exit;
?>
