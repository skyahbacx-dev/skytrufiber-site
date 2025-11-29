<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$client_id = $_SESSION["user_id"] ?? null;
$message   = $_POST["message"] ?? "";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "No client session"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'client', ?, TRUE, FALSE, NOW())
");
$stmt->execute([$client_id, $message]);
$chatId = $conn->lastInsertId();

if (!isset($_FILES["media"]) || empty($_FILES["media"]["name"][0])) {
    echo json_encode(["status" => "ok"]);
    exit;
}

foreach ($_FILES["media"]["name"] as $i => $name) {

    $tmpName  = $_FILES["media"]["tmp_name"][$i];
    $fileType = $_FILES["media"]["type"][$i];
    $fileData = file_get_contents($tmpName);

    $type = "file";
    if (strpos($fileType, "image") !== false) $type = "image";
    elseif (strpos($fileType, "video") !== false) $type = "video";

    $mediaInsert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, media_blob, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $mediaInsert->bindValue(1, $chatId, PDO::PARAM_INT);
    $mediaInsert->bindValue(2, $name, PDO::PARAM_STR);
    $mediaInsert->bindValue(3, $type, PDO::PARAM_STR);
    $mediaInsert->bindValue(4, $fileData, PDO::PARAM_LOB);
    $mediaInsert->execute();
}

echo json_encode(["status" => "ok"]);
exit;
?>
