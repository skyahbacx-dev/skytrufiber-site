<?php
require_once "../../db_connect.php";

$id = $_GET["id"] ?? null;
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

    // Grab the blob value from the row
    $blob = $file["media_blob"];

    // If PDO driver returns a stream resource, read it
    if (is_resource($blob)) {
        $binary = stream_get_contents($blob);
    } else {
        $binary = $blob;
    }

    if ($binary === false || $binary === null) {
        http_response_code(500);
        exit("Empty file");
    }

    // Detect MIME type from binary; fallback to stored media_type
    $mimeType = null;

    if (function_exists("finfo_open")) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected = finfo_buffer($finfo, $binary);
            finfo_close($finfo);
            if ($detected) {
                $mimeType = $detected;
            }
        }
    }

    // If detection failed, pick a reasonable default from media_type
    if (!$mimeType) {
        switch ($file["media_type"]) {
            case "image":
                $mimeType = "image/jpeg";
                break;
            case "video":
                $mimeType = "video/mp4";
                break;
            default:
                $mimeType = "application/octet-stream";
        }
    }

    // Send correct headers
    header("Content-Type: {$mimeType}");
    header("Content-Length: " . strlen($binary));

    // You can add this if you want forced download for non-image/video types:
    // if ($file["media_type"] === "file") {
    //     $name = basename($file["media_path"] ?: "download.bin");
    //     header("Content-Disposition: attachment; filename=\"" . $name . "\"");
    // }

    echo $binary;
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    // Don't echo HTML here; it corrupts the binary response if it happens mid-stream.
    // Log in real code instead:
    // error_log("get_media.php error: " . $e->getMessage());
    exit("Error");
}
