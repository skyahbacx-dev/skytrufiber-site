<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$username = trim($_POST["username"] ?? "");
$message  = trim($_POST["message"] ?? "");

if (!$username) {
    echo json_encode(["status" => "error", "msg" => "no username"]);
    exit;
}

// ----------------------------------------------------------
// Find client by email or full name
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
// BLOCK SENDING IF TICKET IS RESOLVED
// ----------------------------------------------------------
if ($ticket_status === "resolved") {
    echo json_encode([
        "status" => "blocked",
        "msg" => "Ticket already resolved — messaging disabled."
    ]);
    exit;
}

// ----------------------------------------------------------
// Prevent empty messages
// ----------------------------------------------------------
if ($message === "") {
    echo json_encode(["status" => "ok", "msg" => "empty skipped"]);
    exit;
}

// ----------------------------------------------------------
// Insert plain text message
// ----------------------------------------------------------
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$client_id, $message]);

echo json_encode(["status" => "ok"]);
