<?php
if (!isset($_SESSION)) session_start();
require_once "../../db_connect.php";

$id    = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$thumb = isset($_GET["thumb"]) ? (int)$_GET["thumb"] : 0;

if ($id <= 0) exit("invalid");

// Fetch media
$stmt = $conn->prepare("
    SELECT media_type, media_blob, thumb_blob
    FROM chat_media
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) exit("missing");

$mediaType = $file["media_type"];
$isThumb   = ($thumb === 1);

// --------------------------------------------------
// 1.  SERVE THUMBNAIL WHEN REQUESTED
// --------------------------------------------------
if ($isThumb) {

    // If thumbnail exists in DB → serve it
    if (!empty($file["thumb_blob"])) {

        // WebP header (best compression)
        header("Content-Type: image/webp");
        header("Cache-Control: max-age=604800, public");

        echo $file["thumb_blob"];
        exit;
    }

    // If no thumb exists, generate a temporary one
    if ($mediaType === "image") {
        try {
            $im = new Imagick();
            $im->readImageBlob($file["media_blob"]);
            $im->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
            $im->thumbnailImage(300, 300, true);

            // Convert to WebP
            $im->setImageFormat("webp");
            $data = $im->getImageBlob();
            $im->destroy();

            header("Content-Type: image/webp");
            header("Cache-Control: max-age=86400, public");
            echo $data;
            exit;

        } catch (Exception $e) {
            // fallback raw
            header("Content-Type: image/jpeg");
            echo $file["media_blob"];
            exit;
        }
    }

    // Video thumbnails not supported (no FFmpeg)
    if ($mediaType === "video") {
        // Serve a simple placeholder thumbnail
        header("Content-Type: image/png");

        // Transparent PNG 1x1
        echo base64_decode(
            "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAA" .
            "AAC0lEQVR42mP8z8AARwMGAf+1BDcAAAAASUVORK5CYII="
        );
        exit;
    }

    // Other file types
    header("Content-Type: image/png");
    echo base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAA" .
                       "AAC0lEQVR42mP8z8AARwMGAf+1BDcAAAAASUVORK5CYII=");
    exit;
}

// --------------------------------------------------
// 2.  SERVE FULL MEDIA
// --------------------------------------------------
switch ($mediaType) {

    case "image":
        try {
            // Detect format
            $img = new Imagick();
            $img->readImageBlob($file["media_blob"]);
            $format = strtolower($img->getImageFormat()); // jpg/png/webp
            $img->destroy();
        } catch (Exception $e) {
            $format = "jpeg";
        }

        header("Content-Type: image/" . $format);
        header("Cache-Control: max-age=604800, public");
        echo $file["media_blob"];
        exit;

    case "video":
        header("Content-Type: video/mp4");
        header("Cache-Control: max-age=604800, public");
        echo $file["media_blob"];
        exit;

    default:
        // any other file (zip, pdf, etc.)
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename='file'");
        echo $file["media_blob"];
        exit;
}

?>
