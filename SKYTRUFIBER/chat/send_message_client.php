<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$ticketId = (int)($_POST["ticket"] ?? 0);
$message  = trim($_POST["message"] ?? "");

// ----------------------------------------------------------
// VALIDATE TICKET
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

$client_id = (int)$ticket["client_id"];

// ----------------------------------------------------------
// BLOCK EMPTY MESSAGE
// ----------------------------------------------------------
if ($message === "") {
    echo json_encode(["status" => "empty"]);
    exit;
}

// ----------------------------------------------------------
// CHECK FIRST CLIENT MESSAGE (login concern)
// ----------------------------------------------------------
//
// RULE: First client message = ONLY IF:
//   1. Chat contains ZERO existing client messages
//   2. Chat contains ZERO existing CSR messages
//
$chatCountQuery = $conn->prepare("
    SELECT 
        SUM(CASE WHEN sender_type = 'client' THEN 1 ELSE 0 END) AS client_count,
        SUM(CASE WHEN sender_type = 'csr' THEN 1 ELSE 0 END) AS csr_count
    FROM chat
    WHERE ticket_id = ?
");
$chatCountQuery->execute([$ticketId]);
$countRow = $chatCountQuery->fetch(PDO::FETCH_ASSOC);

$existingClientCount = (int)$countRow["client_count"];
$existingCsrCount    = (int)$countRow["csr_count"];

// TRUE only for VERY first message after login
$isFirstLoginMessage = ($existingClientCount === 0 && $existingCsrCount === 0);

// ----------------------------------------------------------
// PREVENT DUPLICATE MESSAGE
// ----------------------------------------------------------
$dupe = $conn->prepare("
    SELECT 1 FROM chat
    WHERE ticket_id = ? AND message = ? AND deleted = FALSE
");
$dupe->execute([$ticketId, $message]);

if ($dupe->fetchColumn()) {
    echo json_encode(["status" => "duplicate"]);
    exit;
}

// ----------------------------------------------------------
// INSERT CLIENT MESSAGE
// ----------------------------------------------------------
$insert = $conn->prepare("
    INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, ?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$ticketId, $client_id, $message]);

// ----------------------------------------------------------
// INSERT CSR AUTO-GREET *ONLY FOR LOGIN FIRST MESSAGE*
// ----------------------------------------------------------
if ($isFirstLoginMessage) {

    $auto = $conn->prepare("
        INSERT INTO chat (
            ticket_id, client_id, sender_type, message, delivered, seen, created_at
        ) VALUES (?, ?, 'csr', 'Good day! How may we assist you today?', TRUE, FALSE, NOW())
    ");
    $auto->execute([$ticketId, $client_id]);

    echo json_encode([
        "status" => "ok",
        "first_message" => true
    ]);
    exit;
}

// ----------------------------------------------------------
// NORMAL RESPONSE
// ----------------------------------------------------------
echo json_encode(["status" => "ok"]);
exit;

?>
