<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

if (!isset($_SESSION["csr_user"])) {
    echo json_encode(["status" => "error", "msg" => "Not authorized"]);
    exit;
}

require_once "../../db_connect.php";

$csrUser   = $_SESSION["csr_user"];
$clientID  = $_POST["client_id"] ?? null;

if (!$clientID || !isset($_FILES["media"])) {
    echo json_encode(["status" => "error", "msg" => "Missing file or client"]);
    exit;
}

$file      = $_FILES["media"];
$filename  = time() . "_" . basename($file["name"]);
$uploadDir = "../../uploads/chat_media/";
$uploadPath = $uploadDir . $filename;

// ensure uploads folder exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!move_uploaded_file($file["tmp_name"], $uploadPath)) {
    echo json_encode(["status" => "error", "msg" => "Upload failed"]);
    exit;
}

try {
    // Record media in chat table
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at, chat_media)
        VALUES (:cid, 'csr', '', false, false, NOW(), :med)
    ");

    $stmt->execute([
        ":cid" => $clientID,
        ":med" => $filename
    ]);

    echo json_encode(["status" => "ok", "media" => $filename]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
