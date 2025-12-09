<?php
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/php_errors.log");

if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$ticketId = (int)($_POST["ticket"] ?? 0);
$message  = trim($_POST["message"] ?? "");

if ($ticketId <= 0) {
    echo json_encode(["status" => "error"]);
    exit;
}

$stmt = $conn->prepare("SELECT client_id, status FROM tickets WHERE id = ?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket || $ticket["status"] === "resolved") {
    echo json_encode(["status" => "blocked"]);
    exit;
}

$clientId = $ticket["client_id"];

if ($message === "") {
    echo json_encode(["status" => "empty"]);
    exit;
}

// Insert client message
$insert = $conn->prepare("
    INSERT INTO chat (ticket_id, client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, ?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$ticketId, $clientId, $message]);

echo json_encode(["status" => "ok"]);
exit;
?>
