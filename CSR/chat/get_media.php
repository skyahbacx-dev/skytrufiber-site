<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit("Missing id");

$stmt = $conn->prepare("SELECT media_blob, media_type FROM chat_media WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    exit("File not found");
}

header("Content-Type: " . ($file["media_type"] === "image" ? "image/jpeg" : "application/octet-stream"));
echo $file["media_blob"];
exit;
?>
