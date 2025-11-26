<?php
require '../db_connect.php';
require '../b2_upload.php';
session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["csr_user"])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$client_id  = intval($_POST["client_id"] ?? 0);
$message    = trim($_POST["message"] ?? "");
$files      = $_FILES["media"] ?? null;

if ($client_id <= 0) {
    echo json_encode(["error" => "Invalid client"]);
    exit;
}

if ($message === "" && (!$files || count($files["name"]) === 0)) {
    echo json_encode(["error" => "Send a message or upload a file"]);
    exit;
}

// Save text message first
$stmt = $pdo->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen)
    VALUES (:cid, 'csr', :msg, TRUE, FALSE)
    RETURNING id
");
$stmt->execute(["cid" => $client_id, "msg" => $message]);
$chat_id = $stmt->fetchColumn();

// Upload media if exists
if ($files) {
    for ($i = 0; $i < count($files["name"]); $i++) {
        if ($files["error"][$i] === 0) {
            $mediaUrl = uploadToB2($files["tmp_name"][$i], $files["name"][$i]);
            
            $pm = $pdo->prepare("
                INSERT INTO chat_media (chat_id, media_path)
                VALUES (:cid, :path)
            ");
            $pm->execute(["cid" => $chat_id, "path" => $mediaUrl]);
        }
    }
}

echo json_encode(["success" => true]);
exit;
