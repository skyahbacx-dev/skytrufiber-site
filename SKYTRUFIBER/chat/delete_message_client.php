<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

$msgID    = (int)($_POST["id"] ?? 0);
$username = trim($_POST["username"] ?? "");

if (!$msgID || $username === "") {
    echo json_encode(["status" => "error", "msg" => "invalid input"]);
    exit;
}

/* -------------------------------------------------
   1) Find client record by email or full_name
------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE email ILIKE ?
       OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "msg" => "user not found"]);
    exit;
}

$client_id = (int)$user["id"];

/* -------------------------------------------------
   2) Ensure this message belongs to this client
      and was sent by the client
------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT id, sender_type, deleted
    FROM chat
    WHERE id = ? AND client_id = ?
    LIMIT 1
");
$stmt->execute([$msgID, $client_id]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$msg) {
    echo json_encode(["status" => "error", "msg" => "message not found"]);
    exit;
}

// Only allow client to delete their own messages
if ($msg["sender_type"] !== "client") {
    echo json_encode(["status" => "error", "msg" => "not your message"]);
    exit;
}

// Already deleted → nothing to do
if (!empty($msg["deleted"])) {
    echo json_encode(["status" => "ok", "type" => "already-deleted"]);
    exit;
}

/* -------------------------------------------------
   3) Soft delete: clear text, mark deleted
   (load_messages_client.php will show placeholder)
------------------------------------------------- */
$upd = $conn->prepare("
    UPDATE chat
    SET message = '', deleted = TRUE, edited = FALSE
    WHERE id = ? AND client_id = ? AND sender_type = 'client'
");
$upd->execute([$msgID, $client_id]);

echo json_encode(["status" => "ok", "type" => "deleted"]);
exit;
