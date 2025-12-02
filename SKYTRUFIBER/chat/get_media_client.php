<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
if (!$id) exit("Missing ID");

// Request thumbnail mode?
$isThumb = isset($_GET["thumb"]) ? true : false;

// Fetch media + thumbnail directly
$stmt = $conn->prepare("
    SELECT media_blob, thumb_blob, media_type 
    FROM chat_media 
    WHERE id = ?
");
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();

$stmt->bindColumn("media_blob", $blob, PDO::PARAM_LOB);
$stmt->bindColumn("thumb_blob", $thumbBlob, PDO::PARAM_LOB);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("Not found");

// Convert blob streams to binary
$binary  = is_resource($blob)      ? stream_get_contents($blob)      : $blob;
$thumb   = is_resource($thumbBlob) ? stream_get_contents($thumbBlob) : $thumbBlob;

// Detect real MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_buffer($finfo, $binary);
finfo_close($finfo);

if (!$mimeType) $mimeType = "application/octet-stream";

/* ======================================================
   RETURN THUMBNAIL IF REQUESTED & EXISTS
====================================================== */
if ($isThumb && !empty($thumb) && strpos($mimeType, "image") === 0) {
    header("Content-Type: image/jpeg");
    header("Cache-Control: public, max-age=86400");
    echo $thumb;
    exit;
}

/* ======================================================
   FALLBACK THUMBNAIL (generate on the fly once)
====================================================== */
if ($isThumb && empty($thumb) && strpos($mimeType, "image") === 0) {

    $image = @imagecreatefromstring($binary);
    if ($image) {
        $maxW = 250;
        $scaled = @imagescale($image, $maxW);

        ob_start();
        imagejpeg($scaled, null, 70);
        $generatedThumb = ob_get_clean();

        imagedestroy($image);
        imagedestroy($scaled);

        // Store generated thumbnail in DB (backfill)
        $update = $conn->prepare("UPDATE chat_media SET thumb_blob = ? WHERE id = ?");
        $update->bindValue(1, $generatedThumb, PDO::PARAM_LOB);
        $update->bindValue(2, $id, PDO::PARAM_INT);
        $update->execute();

        header("Content-Type: image/jpeg");
        echo $generatedThumb;
        exit;
    }
}

/* ======================================================
   FULL RESOLUTION MEDIA
====================================================== */
header("Content-Type: $mimeType");
header("Content-Length: " . strlen($binary));
header("Cache-Control: no-cache, no-store, must-revalidate");

echo $binary;
exit;
?>
