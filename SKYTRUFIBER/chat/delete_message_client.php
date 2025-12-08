<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

$msgID  = (int)($_POST["id"] ?? 0);
$ticket = (int)($_POST["ticket"] ?? 0);

if (!$msgID || !$ticket) {
    echo json_encode(["status" => "error", "msg" => "Invalid input"]);
    exit;
}

/* -------------------------------------------------
   1) Ensure this message belongs to this ticket
      and was sent by the client
------------------------------------------------- */
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

// Only allow client to delete their own messages
if ($msg["sender_type"] !== "client") {
    echo json_encode(["status" => "error", "msg" => "Not your message"]);
    exit;
}

// Already deleted → nothing to do
if (!empty($msg["deleted"])) {
    echo json_encode(["status" => "ok", "type" => "already-deleted"]);
    exit;
}

/* -------------------------------------------------
   2) Soft delete: clear text, mark deleted
   (load_messages_client.php will show placeholder)
------------------------------------------------- */
$upd = $conn->prepare("
    UPDATE chat
    SET message = '', deleted = TRUE, edited = FALSE
    WHERE id = ? AND ticket_id = ? AND sender_type = 'client'
");
$upd->execute([$msgID, $ticket]);

echo json_encode(["status" => "ok", "type" => "deleted"]);
exit;
?>
