<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../../db_connect.php";

$ticketId = intval($_POST["ticket"] ?? 0);
$message  = trim($_POST["message"] ?? "");

if ($ticketId <= 0) {
    echo json_encode(["status" => "error", "msg" => "invalid_ticket"]);
    exit;
}

// Fetch ticket
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

// Security: correct session client
if (!isset($_SESSION["client_id"]) || $_SESSION["client_id"] != $ticket["client_id"]) {
    echo json_encode(["status" => "error", "msg" => "not_authorized"]);
    exit;
}

if ($ticket["status"] === "resolved") {
    $_SESSION["force_logout"] = true;
    echo json_encode(["status" => "blocked", "msg" => "ticket_resolved"]);
    exit;
}

if ($message === "") {
    echo json_encode(["status" => "empty"]);
    exit;
}

// Check if first client message
$check = $conn->prepare("
    SELECT COUNT(*) FROM chat
    WHERE ticket_id = ? AND sender_type = 'client'
");
$check->execute([$ticketId]);
$isFirst = ($check->fetchColumn() == 0);

// Insert message
$insert = $conn->prepare("
    INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, ?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$ticketId, $ticket["client_id"], $message]);

if ($isFirst) {
    $_SESSION["show_suggestions"] = true;
    echo json_encode(["status" => "ok", "first_message" => true]);
    exit;
}

echo json_encode(["status" => "ok"]);
exit;
