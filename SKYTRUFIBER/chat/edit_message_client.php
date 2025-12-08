<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

$id      = (int)($_POST["id"] ?? 0);
$message = trim($_POST["message"] ?? "");
$username = trim($_POST["username"] ?? "");

if ($id <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid message ID"]);
    exit;
}

if ($message === "") {
    echo json_encode(["status" => "error", "msg" => "Message cannot be empty"]);
    exit;
}

if ($username === "") {
    echo json_encode(["status" => "error", "msg" => "Missing username"]);
    exit;
}

/* -------------------------------------------------
   1) Identify the client by email or full name
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
    echo json_encode(["status" => "error", "msg" => "User not found"]);
    exit;
}

$client_id = (int)$user["id"];

/* -------------------------------------------------
   2) Validate the message belongs to this client
      AND was sent by the client
------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT sender_type, deleted
    FROM chat
    WHERE id = ? AND client_id = ?
    LIMIT 1
");
$stmt->execute([$id, $client_id]);
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

/* -------------------------------------------------
   3) Update the message text
------------------------------------------------- */
$update = $conn->prepare("
    UPDATE chat
    SET message = ?, edited = TRUE, updated_at = NOW()
    WHERE id = ? AND sender_type = 'client'
");
$update->execute([$message, $id]);

echo json_encode(["status" => "success"]);
exit;
