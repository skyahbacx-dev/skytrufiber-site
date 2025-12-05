<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
$thumb = isset($_GET["thumb"]); // thumbnail mode

if (!$id) {
    http_response_code(400);
    exit("Missing ID");
}

$stmt = $conn->prepare("
    SELECT media_blob, media_path, media_type
    FROM chat_media
    WHERE id = ?
");
$stmt->bindValue(1, $id, PDO::PARAM_INT);
$stmt->execute();

$stmt->bindColumn("media_blob", $blob, PDO::PARAM_LOB);
$media = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$media) {
    http_response_code(404);
    exit("Not found");
}

/* ============================================================
   READ BLOB CONTENT
============================================================ */
if (is_resource($blob)) {
    $binary = stream_get_contents($blob);
} else {
    $binary = $blob;
}

if (!$binary) {
    http_response_code(500);
    exit("Error reading file");
}

/* ============================================================
   DETECT MIME TYPE
============================================================ */
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_buffer($finfo, $binary);
finfo_close($finfo);

if (!$mimeType) $mimeType = "application/octet-stream";


/* ============================================================
   HANDLE THUMBNAIL MODE
   (For images only, reduce size for faster loading)
============================================================ */
if ($thumb && strpos($mimeType, "image") === 0) {

    // Create thumbnail (max 300px)
    $img = @imagecreatefromstring($binary);

    if ($img !== false) {

        $w = imagesx($img);
        $h = imagesy($img);

        $max = 300; // thumbnail size
        $scale = min($max / $w, $max / $h);

        $newW = intval($w * $scale);
        $newH = intval($h * $scale);

        $thumbImg = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($thumbImg, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);

        ob_start();
        imagejpeg($thumbImg, null, 80);
        $binary = ob_get_clean();

        imagedestroy($img);
        imagedestroy($thumbImg);

        $mimeType = "image/jpeg";
    }
}


/* ============================================================
   SUPPORT VIDEO STREAMING (Range Requests)
============================================================ */
if (strpos($mimeType, "video") === 0) {

    $filesize = strlen($binary);
    $start = 0;
    $end = $filesize - 1;

    if (isset($_SERVER['HTTP_RANGE'])) {

        preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches);

        $start = intval($matches[1]);

        if (!empty($matches[2])) {
            $end = intval($matches[2]);
        }

        if ($end > $filesize - 1) $end = $filesize - 1;

        header("HTTP/1.1 206 Partial Content");
    }

    $length = $end - $start + 1;

    header("Content-Type: $mimeType");
    header("Accept-Ranges: bytes");
    header("Content-Length: $length");
    header("Content-Range: bytes $start-$end/$filesize");

    echo substr($binary, $start, $length);
    exit;
}


/* ============================================================
   DEFAULT FILE OUTPUT (images, pdf, attachments)
============================================================ */
header("Content-Type: $mimeType");
header("Content-Length: " . strlen($binary));

echo $binary;
exit;
