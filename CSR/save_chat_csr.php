<?php
session_start();
require "../db_connect.php";

$clientId = $_POST["client_id"] ?? null;
$message  = trim($_POST["message"] ?? "");
$sender   = "csr";

if (!$clientId || $message === "") {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}

$sql = "
    INSERT INTO chat (client_id, sender_type, message, delivered, seen)
    VALUES (:client, :sender, :msg, TRUE, FALSE)
    RETURNING id, created_at
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":client" => $clientId,
    ":sender" => $sender,
    ":msg"    => $message
]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "status"  => "success",
    "chat_id" => $result["id"],
    "created" => $result["created_at"]
]);
