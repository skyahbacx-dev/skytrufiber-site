<?php
ini_set("session.name", "CSRSESSID");
session_start();

require_once "../../db_connect.php";

header("Content-Type: application/json; charset=utf-8");

$id      = (int)($_POST["id"] ?? 0);
$message = trim($_POST["message"] ?? "");
$csrUser = $_SESSION["csr_user"] ?? null;

if ($id <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid ID"]);
    exit;
}

if ($message === "") {
    echo json_encode(["status" => "error", "msg" => "Message cannot be empty"]);
    exit;
}

if (!$csrUser) {
    echo json_encode(["status" => "error", "msg" => "CSR not logged in"]);
    exit;
}

/* -------------------------------------------------
   1) Fetch the message and validate ownership
------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT c.sender_type, c.deleted, u.assigned_csr
    FROM chat c
    JOIN users u ON c.client_id = u.id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$msg = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$msg) {
    echo json_encode(["status" => "error", "msg" => "Message not found"]);
    exit;
}

if ($msg["sender_type"] !== "csr") {
    echo json_encode(["status" => "error", "msg" => "Cannot edit client messages"]);
    exit;
}

if ($msg["deleted"]) {
    echo json_encode(["status" => "error", "msg" => "Cannot edit deleted message"]);
    exit;
}

if ($msg["assigned_csr"] !== $csrUser) {
    echo json_encode(["status" => "error", "msg" => "Not authorized (client not assigned to you)"]);
    exit;
}

/* -------------------------------------------------
   2) Update the message
------------------------------------------------- */
$update = $conn->prepare("
    UPDATE chat
    SET message = ?, edited = TRUE, updated_at = NOW()
    WHERE id = ?
");
$update->execute([$message, $id]);

echo json_encode(["status" => "success"]);
exit;
