<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

$msgID  = (int)($_POST["id"] ?? 0);
$ticket = (int)($_POST["ticket"] ?? 0);

// ----------------------------------------------------------
// VALIDATE INPUT
// ----------------------------------------------------------
if (!$msgID || !$ticket) {
    echo json_encode(["status" => "error", "msg" => "Invalid input"]);
    exit;
}

// ----------------------------------------------------------
// VALIDATE MESSAGE BELONGS TO THIS TICKET AND IS CLIENT-SENT
// ----------------------------------------------------------
$stmt = $conn->prepare("
    SELECT id, sender_type, deleted
    FROM chat
    WHERE id = ? AND ticket_id = ?
    LIMIT 1
");
$stmt->execute([$msgID, $ticket]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$msg) {
    echo json_encode(["status" => "error", "msg" => "Message not found"]);
    exit;
}

if ($msg["sender_type"] !== "client") {
    echo json_encode(["status" => "error", "msg" => "Not your message"]);
    exit;
}

// Already deleted → nothing to do
if (!empty($msg["deleted"])) {
    echo json_encode(["status" => "ok", "type" => "already-deleted"]);
    exit;
}

// ----------------------------------------------------------
// SOFT DELETE MESSAGE (clear text, mark deleted)
// ----------------------------------------------------------
$upd = $conn->prepare("
    UPDATE chat
    SET message = '', deleted = TRUE, edited = FALSE
    WHERE id = ? AND ticket_id = ? AND sender_type = 'client'
");
$upd->execute([$msgID, $ticket]);

echo json_encode(["status" => "ok", "type" => "deleted"]);
exit;
?>
