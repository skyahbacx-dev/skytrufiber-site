<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit("Missing ID");

// Is request for thumbnail version? (for fast chat bubble preview)
$isThumb = isset($_GET["thumb"]) ? true : false;

$stmt = $conn->prepare("SELECT media_blob, media_type FROM chat_media WHERE id = ?");
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();

// Bind blob as stream
$stmt->bindColumn("media_blob", $blob, PDO::PARAM_LOB);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("Not found");

// Extract binary data
if (is_resource($blob)) {
    $binary = stream_get_contents($blob);
} else {
    $binary = $blob;
}

// Detect MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_buffer($finfo, $binary);
finfo_close($finfo);

if (!$mimeType) $mimeType = "application/octet-stream";

// ======================================
// THUMBNAIL MODE (small optimized preview)
// ======================================
if ($isThumb && strpos($mimeType, "image") === 0) {

    $image = imagecreatefromstring($binary);
    if ($image) {
        $maxWidth = 250; // thumbnail width
        $thumb = imagescale($image, $maxWidth); // proportional resize

        header("Content-Type: image/jpeg");
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");

        imagejpeg($thumb, null, 70); // quality 70 for fast load

        imagedestroy($image);
        imagedestroy($thumb);
        exit;
    }
}

// ======================================
// FULL IMAGE / VIDEO STREAM
// ======================================

header("Content-Type: $mimeType");
header("Content-Length: " . strlen($binary));
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

echo $binary;
exit;
?>
