<?php
require_once "../../db_connect.php";

$id = (int)($_GET["id"] ?? 0);
$thumb = isset($_GET["thumb"]);

// Fetch media
$stmt = $conn->prepare("
    SELECT media_type, media_path, media_blob, thumb_blob
    FROM chat_media
    WHERE id = $1
    LIMIT 1
");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    exit("Not found");
}

$mediaType = $file["media_type"];
$filename  = $file["media_path"];

// Choose blob: thumbnail OR full
$data = ($thumb && $file["thumb_blob"])
    ? $file["thumb_blob"]
    : $file["media_blob"];

// ==== Set proper headers ====

if ($mediaType === "image") {

    header("Content-Type: image/jpeg");
    header("Content-Length: " . strlen($data));
    header("Content-Disposition: inline; filename=\"$filename\"");
    echo $data;
    exit;
}

if ($mediaType === "video") {

    header("Content-Type: video/mp4");
    header("Content-Length: " . strlen($data));
    header("Content-Disposition: inline; filename=\"$filename\"");
    echo $data;
    exit;
}

// Fallback for ANY other file
header("Content-Type: application/octet-stream");
header("Content-Length: " . strlen($data));
header("Content-Disposition: attachment; filename=\"$filename\"");
echo $data;
exit;
