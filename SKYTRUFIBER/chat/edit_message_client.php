<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

$id      = (int)($_POST["id"] ?? 0);
$ticket  = (int)($_POST["ticket"] ?? 0);
$message = trim($_POST["message"] ?? "");

// ----------------------------------------------------------
// VALIDATE INPUT
// ----------------------------------------------------------
if ($id <= 0 || $ticket <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid parameters"]);
    exit;
}

if ($message === "") {
    echo json_encode(["status" => "error", "msg" => "Message cannot be empty"]);
    exit;
}

// ----------------------------------------------------------
// VALIDATE MESSAGE BELONGS TO THIS TICKET AND IS CLIENT-SENT
// ----------------------------------------------------------
$stmt = $conn->prepare("
    SELECT sender_type, deleted
    FROM chat
    WHERE id = ? AND ticket_id = ?
    LIMIT 1
");
$stmt->execute([$id, $ticket]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$msg) {
    echo json_encode(["status" => "error", "msg" => "Message not found"]);
    exit;
}

if ($msg["sender_type"] !== "client") {
    echo json_encode(["status" => "error", "msg" => "Cannot edit CSR messages"]);
    exit;
}

if ($msg["deleted"]) {
    echo json_encode(["status" => "error", "msg" => "Cannot edit a deleted message"]);
    exit;
}

// ----------------------------------------------------------
// PREVENT DUPLICATE EDIT
// ----------------------------------------------------------
$dup = $conn->prepare("
    SELECT 1 FROM chat
    WHERE id = ? AND message = ? AND deleted = FALSE
    LIMIT 1
");
$dup->execute([$id, $message]);

if ($dup->fetchColumn()) {
    echo json_encode(["status" => "same", "msg" => "No changes detected"]);
    exit;
}

// ----------------------------------------------------------
// UPDATE MESSAGE
// ----------------------------------------------------------
$update = $conn->prepare("
    UPDATE chat
    SET message = ?, edited = TRUE, updated_at = NOW()
    WHERE id = ? AND sender_type = 'client'
");
$update->execute([$message, $id]);

echo json_encode(["status" => "success"]);
exit;
?>
