<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$ticketId = (int)($_POST["ticket"] ?? 0);
$message  = trim($_POST["message"] ?? "");

// -------------------- VALIDATE --------------------
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

// -------------------- EMPTY MESSAGE --------------------
if ($message === "") {
    echo json_encode(["status" => "empty"]);
    exit;
}

// -------------------- CHECK IF THIS IS FIRST CLIENT MESSAGE --------------------
$firstCheck = $conn->prepare("
    SELECT COUNT(*) 
    FROM chat 
    WHERE ticket_id = ? AND sender_type = 'client'
");
$firstCheck->execute([$ticketId]);
$isFirstMessage = ($firstCheck->fetchColumn() == 0);

// -------------------- PREVENT DUPLICATE --------------------
$dupe = $conn->prepare("
    SELECT 1 FROM chat
    WHERE ticket_id = ? AND message = ? AND deleted = FALSE
");
$dupe->execute([$ticketId, $message]);

if ($dupe->fetchColumn()) {
    echo json_encode(["status" => "duplicate"]);
    exit;
}

// -------------------- INSERT CLIENT MESSAGE --------------------
$insert = $conn->prepare("
    INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, ?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$ticketId, $client_id, $message]);

// -------------------- CSR AUTO-GREET FOR FIRST CLIENT MESSAGE --------------------
if ($isFirstMessage) {

    $auto = $conn->prepare("
        INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, seen, created_at)
        VALUES (?, ?, 'csr', 'Good day! How may we assist you today?', TRUE, FALSE, NOW())
    ");
    $auto->execute([$ticketId, $client_id]);

    echo json_encode([
        "status" => "ok",
        "first_message" => true
    ]);
    exit;
}

// -------------------- NORMAL RESPONSE --------------------
echo json_encode(["status" => "ok"]);
exit;

?>
