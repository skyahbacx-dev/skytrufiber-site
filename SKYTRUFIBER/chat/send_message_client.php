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
// CHECK MESSAGE COUNTS
// ----------------------------------------------------------
//
// This identifies the FIRST post-login message correctly.
// Very first message = no client messages + no CSR messages.
//
// Note:
// login concern message ALSO counts as a client message.
// So this prevents double-auto-greet and avoids duplicates.
// ----------------------------------------------------------

$countQuery = $conn->prepare("
    SELECT 
        SUM(CASE WHEN sender_type = 'client' THEN 1 ELSE 0 END) AS client_count,
        SUM(CASE WHEN sender_type = 'csr' THEN 1 ELSE 0 END) AS csr_count
    FROM chat
    WHERE ticket_id = ?
");
$countQuery->execute([$ticketId]);
$count = $countQuery->fetch(PDO::FETCH_ASSOC);

$clientCount = (int)$count["client_count"];
$csrCount    = (int)$count["csr_count"];

// TRUE only if ticket is 100% empty before this message
$isFirstLoginMessage = ($clientCount === 0 && $csrCount === 0);

// ----------------------------------------------------------
// PREVENT DUPLICATE MESSAGE
// ----------------------------------------------------------
$dupe = $conn->prepare("
    SELECT 1 FROM chat
    WHERE ticket_id = ? AND message = ? AND deleted = FALSE
    LIMIT 1
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
// AUTO-GREET ONLY FOR FIRST LOGIN MESSAGE
// ----------------------------------------------------------
//
// If this was truly the first message after login (ticket empty),
// backend inserts the CSR greeting immediately.
//
// load_messages_client.php does NOT add any greeting in this case.
// ----------------------------------------------------------
if ($isFirstLoginMessage) {

    $auto = $conn->prepare("
        INSERT INTO chat (
            ticket_id, client_id, sender_type, message, delivered, seen, created_at
        ) VALUES (?, ?, 'csr', 'Good day! How may we assist you today?', TRUE, FALSE, NOW())
    ");
    $auto->execute([$ticketId, $client_id]);

    // Tell JS to show suggestion bubble
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
