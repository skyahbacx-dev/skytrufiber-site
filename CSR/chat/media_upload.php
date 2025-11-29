<?php
if (!isset($_SESSION)) session_start();
header("Content-Type: application/json");

require_once "../../db_connect.php";

$client_id = $_POST["client_id"] ?? null;
$csr       = $_SESSION["csr_user"] ?? null;

if (!$client_id || !$csr) {
    echo json_encode(["status" => "error", "msg" => "Missing data"]);
    exit;
}

if (empty($_FILES["media"]["name"])) {
    echo json_encode(["status" => "error", "msg" => "No files received"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, delivered, seen, created_at)
    VALUES (?, 'csr', '', TRUE, FALSE, NOW())
");
$stmt->execute([$client_id]);
$chatId = $conn->lastInsertId();

foreach ($_FILES["media"]["tmp_name"] as $i => $tmpName) {

    $fileName = $_FILES["media"]["name"][$i];
    $fileType = $_FILES["media"]["type"][$i];
    $fileBlob = file_get_contents($tmpName);

    $type = "file";
    if (strpos($fileType, "image") !== false) $type = "image";
    elseif (strpos($fileType, "video") !== false) $type = "video";

    $insert = $conn->prepare("
        INSERT INTO chat_media (chat_id, media_path, media_type, media_blob)
        VALUES (?, ?, ?, ?)
    ");
    $insert->bindParam(1, $chatId);
    $insert->bindParam(2, $fileName);
    $insert->bindParam(3, $type);
    $insert->bindParam(4, $fileBlob, PDO::PARAM_LOB);
    $insert->execute();
}

echo json_encode(["status" => "ok"]);
exit;
?>
