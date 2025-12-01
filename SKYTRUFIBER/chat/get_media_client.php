<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
$isThumb = isset($_GET["thumb"]);

if (!$id) exit("Missing ID");

$stmt = $conn->prepare("SELECT media_blob, media_type FROM chat_media WHERE id = ?");
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();
$stmt->bindColumn("media_blob", $blob, PDO::PARAM_LOB);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("Not found");

if (is_resource($blob)) {
    $binary = stream_get_contents($blob);
} else {
    $binary = $blob;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_buffer($finfo, $binary);
finfo_close($finfo);

if (!$mimeType) $mimeType = "application/octet-stream";

// ---------------------------
// Thumbnail for IMAGES only
// ---------------------------
if ($isThumb && strpos($mimeType, "image") === 0) {
    $image = imagecreatefromstring($binary);

    if ($image !== false) {
        $thumb = imagescale($image, 250);

        header("Content-Type: image/jpeg");
        header("Cache-Control: no-store, no-cache, must-revalidate");

        imagejpeg($thumb, null, 70);

        imagedestroy($image);
        imagedestroy($thumb);
        exit;
    }
}

// ---------------------------
// FULL CONTENT RESPONSE
// ---------------------------
header("Content-Type: $mimeType");
header("Cache-Control: no-store, no-cache, must-revalidate");
echo $binary;
exit;
