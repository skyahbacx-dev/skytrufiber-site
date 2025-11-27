<?php
require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
if (!$client_id || !isset($_FILES["media"])) exit;

$uploadDir = "../upload/chat_images/";
$filename = time() . "_" . basename($_FILES["media"]["name"]);
$path = $uploadDir . $filename;

move_uploaded_file($_FILES["media"]["tmp_name"], $path);

$stmt = $conn->prepare("
    INSERT INTO chat_media (chat_id, media_path, media_type) VALUES
    ((SELECT MAX(id) FROM chat WHERE client_id=?), ?, ?)
");
$stmt->execute([$client_id, $filename, $_FILES["media"]["type"]]);

echo "OK";
