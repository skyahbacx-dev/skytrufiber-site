<?php
// b2_upload.php
header('Content-Type: application/json');

$bucketName  = "ahba-chat-media";
$endpoint    = "https://s3.us-east-005.backblazeb2.com";
$keyId       = "005a548887f9c4f0000000002";
$appKey      = "K005fOYaprINPto/Qdm9wex0w4v/L2k";

// BLOCK IF WRONG REQUEST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    echo json_encode(["status" => "error", "message" => "Invalid upload request"]);
    exit;
}

// FILE DETAILS
$file      = $_FILES['file'];
$filename  = time() . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $file["name"]);
$fileType  = mime_content_type($file["tmp_name"]);
$fileSize  = $file["size"];

// ALLOWED TYPES
$allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp", "video/mp4", "video/mpeg", "video/quicktime"];

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(["status" => "error", "message" => "Unsupported file type"]);
    exit;
}

// INIT CURL
$ch = curl_init("$endpoint/$bucketName/$filename");
curl_setopt($ch, CURLOPT_PUT, true);
curl_setopt($ch, CURLOPT_INFILE, fopen($file["tmp_name"], 'rb'));
curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic " . base64_encode("$keyId:$appKey"),
    "Content-Type: $fileType",
    "x-amz-acl: public-read"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $publicURL = "$endpoint/$bucketName/$filename";

    echo json_encode([
        "status" => "success",
        "url" => $publicURL
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Upload failed",
        "code" => $httpCode,
        "response" => $response
    ]);
}
?>
