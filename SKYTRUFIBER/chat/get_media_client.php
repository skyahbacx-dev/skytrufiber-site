<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit("Missing ID");

// Thumbnail flag
$isThumb = isset($_GET["thumb"]);

// Fetch file
$stmt = $conn->prepare("SELECT media_blob, media_type FROM chat_media WHERE id = ?");
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();

$stmt->bindColumn("media_blob", $blob, PDO::PARAM_LOB);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("Not found");

// Get binary content from BLOB
$binary = is_resource($blob) ? stream_get_contents($blob) : $blob;

// Determine MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_buffer($finfo, $binary);
finfo_close($finfo);

// Default MIME if undetected
if (!$mimeType) $mimeType = "application/octet-stream";


// ==========================================
// THUMBNAIL GENERATION (IMAGE ONLY)
// ==========================================
if ($isThumb && strpos($mimeType, "image") === 0) {

    $image = imagecreatefromstring($binary);
    if ($image) {

        $maxWidth = 250;
        $thumb = imagescale($image, $maxWidth);

        header("Content-Type: image/jpeg");
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");

        imagejpeg($thumb, null, 70); // fast compressed preview

        imagedestroy($image);
        imagedestroy($thumb);
        exit;
    }
}


// ==========================================
// BYTE-RANGE STREAMING FOR VIDEO
// ==========================================
if (strpos($mimeType, "video") === 0) {

    header("Accept-Ranges: bytes");

    $fileSize = strlen($binary);
    $start = 0;
    $end = $fileSize - 1;

    if (isset($_SERVER['HTTP_RANGE'])) {

        $range = str_replace('bytes=', '', $_SERVER['HTTP_RANGE']);
        $range = explode('-', $range);

        $start = intval($range[0]);
        $end = isset($range[1]) && is_numeric($range[1]) ? intval($range[1]) : $end;

        header("HTTP/1.1 206 Partial Content");
    }

    $length = $end - $start + 1;

    header("Content-Type: $mimeType");
    header("Content-Length: $length");
    header("Content-Range: bytes $start-$end/$fileSize");
    header("Cache-Control: no-cache, no-store, must-revalidate");

    echo substr($binary, $start, $length);
    exit;
}


// ==========================================
// DEFAULT IMAGE / FILE OUTPUT
// ==========================================
header("Content-Type: $mimeType");
header("Content-Length: " . strlen($binary));
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

echo $binary;
exit;

?>
