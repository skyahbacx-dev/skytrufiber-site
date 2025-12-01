<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$username = $_POST["username"] ?? null;
$message  = $_POST["message"] ?? "";

if (!$username) {
    echo json_encode(["status"=>"error","msg"=>"Missing username"]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$username]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$client) exit(json_encode(["status"=>"error","msg"=>"Invalid user"]));

$client_id = (int)$client["id"];

// Create chat entry
$insert = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$insert->execute([$client_id, $message]);
$chatId = $conn->lastInsertId();

// Handle files
foreach ($_FILES["media"]["name"] as $i => $name) {
    $tmp = $_FILES["media"]["tmp_name"][$i];
    $type = $_FILES["media"]["type"][$i];

    $blob = file_get_contents($tmp);
    if (!$blob) continue;

    $mediaType = "file";
    if (strpos($type, "image") !== false) $mediaType = "image";
    elseif (strpos($type, "video") !== false) $mediaType = "video";

    $m = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, media_blob, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $m->bindValue(1, $chatId, PDO::PARAM_INT);
    $m->bindValue(2, $name, PDO::PARAM_STR);
    $m->bindValue(3, $mediaType, PDO::PARAM_STR);
    $m->bindValue(4, $blob, PDO::PARAM_LOB);
    $m->execute();
}

echo json_encode(["status" => "ok"]);
exit;
?>
