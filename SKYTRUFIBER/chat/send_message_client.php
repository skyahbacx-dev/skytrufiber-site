<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
$message  = trim($_POST["message"] ?? "");

// ----------------------------------------------------------
// VALIDATE USERNAME
// ----------------------------------------------------------
if ($username === "") {
    echo json_encode(["status" => "error", "msg" => "no username"]);
    exit;
}

// ----------------------------------------------------------
// FIND CLIENT ACCOUNT
// ----------------------------------------------------------
$stmt = $conn->prepare("
    SELECT id, ticket_status
    FROM users
    WHERE email ILIKE ?
       OR full_name ILIKE ?
    LIMIT 1
");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "msg" => "invalid user"]);
    exit;
}

$client_id     = (int)$user["id"];
$ticket_status = $user["ticket_status"] ?? "unresolved";

// ----------------------------------------------------------
// BLOCK MESSAGE IF TICKET IS RESOLVED
// ----------------------------------------------------------
if ($ticket_status === "resolved") {
    echo json_encode([
        "status" => "blocked",
        "msg" => "Ticket already resolved — messaging disabled."
    ]);
    exit;
}

// ----------------------------------------------------------
// PREVENT EMPTY MESSAGE
// ----------------------------------------------------------
if ($message === "") {
    echo json_encode(["status" => "ok", "msg" => "empty skipped"]);
    exit;
}

// ----------------------------------------------------------
// PREVENT GREETING DUPLICATION
// If this is the auto-greeting or quick reply text,
// and the same message exists already → skip.
// ----------------------------------------------------------
$check = $conn->prepare("
    SELECT 1
    FROM chat
    WHERE client_id = ?
      AND sender_type = 'client'
      AND message = ?
      AND deleted = FALSE
    LIMIT 1
");
$check->execute([$client_id, $message]);
$exists = $check->fetchColumn();

if ($exists) {
    echo json_encode(["status" => "duplicate", "msg" => "message already exists"]);
    exit;
}

// ----------------------------------------------------------
// INSERT CLIENT TEXT MESSAGE
// ----------------------------------------------------------
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$client_id, $message]);

echo json_encode(["status" => "ok"]);
