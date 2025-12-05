<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
$thumb = isset($_GET["thumb"]); // TRUE if requesting thumbnail

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

    // Convert LOB stream to string if necessary
    if (is_resource($blob)) {
        $binary = stream_get_contents($blob);
    } else {
        $binary = $blob;
    }

    if (!$binary) {
        http_response_code(500);
        exit("Empty file");
    }

    $type = $file["media_type"]; // image / video / file


    /* ==========================================================
       THUMBNAIL MODE
    ========================================================== */
    if ($thumb) {

        // Thumbnails supported ONLY for media_type="image" or "video"
        if ($type === "image") {

            // Try to create resized JPEG thumbnail
            $srcImg = @imagecreatefromstring($binary);

            if ($srcImg) {
                $w = imagesx($srcImg);
                $h = imagesy($srcImg);

                // Resize to width 300px max (grid size)
                $maxW = 300;
                $ratio = $maxW / $w;
                $thumbW = $maxW;
                $thumbH = intval($h * $ratio);

                $thumbImg = imagecreatetruecolor($thumbW, $thumbH);
                imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0, $thumbW, $thumbH, $w, $h);

                header("Content-Type: image/jpeg");
                ob_start();
                imagejpeg($thumbImg, null, 70);
                $output = ob_get_clean();

                imagedestroy($thumbImg);
                imagedestroy($srcImg);

                echo $output;
                exit;
            }

            // fallback if thumbnail fails
            header("Content-Type: image/jpeg");
            echo $binary;
            exit;
        }

        elseif ($type === "video") {
            // Return STILL FULL video but muted thumbnail – frontend uses <video> element
            header("Content-Type: video/mp4");
            echo $binary;
            exit;
        }

        else {
            // Files do not have thumbnails → return normal binary
            header("Content-Type: application/octet-stream");
            echo $binary;
            exit;
        }
    }


    /* ==========================================================
       FULL MEDIA OUTPUT (NORMAL MODE)
    ========================================================== */

    // MIME detection
    $mimeType = null;

    if (function_exists("finfo_open")) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) {
            $mimeType = finfo_buffer($f, $binary);
            finfo_close($f);
        }
    }

    // fallback MIME types
    if (!$mimeType) {
        switch ($type) {
            case "image": $mimeType = "image/jpeg"; break;
            case "video": $mimeType = "video/mp4"; break;
            default:      $mimeType = "application/octet-stream";
        }
    }

    header("Content-Type: {$mimeType}");
    header("Content-Length: " . strlen($binary));

    echo $binary;
    exit;


} catch (Throwable $e) {
    http_response_code(500);
    exit("Error");
}
