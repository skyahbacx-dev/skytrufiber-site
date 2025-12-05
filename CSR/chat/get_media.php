<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
$thumb = isset($_GET["thumb"]);  // detect thumbnail mode

if (!$id) {
    http_response_code(400);
    exit("Missing ID");
}

try {
    $stmt = $conn->prepare("
        SELECT media_blob, media_path, media_type
        FROM chat_media
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([(int)$id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        http_response_code(404);
        exit("Not found");
    }

    $blob = $file["media_blob"];
    $binary = is_resource($blob) ? stream_get_contents($blob) : $blob;

    if (!$binary) {
        http_response_code(500);
        exit("Empty file");
    }

    /* ============================
       THUMBNAIL MODE
    ============================ */
    if ($thumb && $file["media_type"] === "image") {

        $srcImg = imagecreatefromstring($binary);

        if ($srcImg) {
            $width = imagesx($srcImg);
            $height = imagesy($srcImg);

            $maxWidth = 200;   // thumbnail width
            $ratio = $maxWidth / $width;

            $thumbW = $maxWidth;
            $thumbH = intval($height * $ratio);

            $thumbImg = imagecreatetruecolor($thumbW, $thumbH);
            imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0,
                $thumbW, $thumbH, $width, $height
            );

            ob_start();
            imagejpeg($thumbImg, null, 70);
            $binary = ob_get_clean();

            header("Content-Type: image/jpeg");
            echo $binary;
            exit;
        }
    }

    /* ============================
       AUTO MIME DETECTION
    ============================ */
    $mime = null;
    if (function_exists("finfo_open")) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) {
            $mime = finfo_buffer($f, $binary);
            finfo_close($f);
        }
    }

    if (!$mime) {
        $mime = match ($file["media_type"]) {
            "image" => "image/jpeg",
            "video" => "video/mp4",
            default => "application/octet-stream"
        };
    }

    header("Content-Type: $mime");
    header("Content-Length: " . strlen($binary));

    echo $binary;
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    exit("Error");
}
