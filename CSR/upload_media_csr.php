<?php
session_start();
require "../db_conn.php";

if (!isset($_SESSION['csr_user'])) exit("Unauthorized");

$client_id = $_POST["client_id"];
$file = $_FILES["file"];

$bucketName = "ahba-chat-media";
$endpoint = "https://s3.us-east-005.backblazeb2.com";
$keyId = "005a548887f9c4f0000000002";
$appKey = "K005fOYaprINPto/Qdm9wex0w4v/L2k";

$filename = "CSR_" . time() . "_" . basename($file["name"]);
$path = $endpoint . "/" . $bucketName . "/" . $filename;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $path,
    CURLOPT_PUT => true,
    CURLOPT_INFILE => fopen($file["tmp_name"], "r"),
    CURLOPT_INFILESIZE => filesize($file["tmp_name"]),
    CURLOPT_HTTPHEADER => [
        "Authorization: Basic " . base64_encode("$keyId:$appKey"),
        "Content-Type: " . mime_content_type($file["tmp_name"])
    ]
]);

curl_exec($ch);
curl_close($ch);

$type = explode("/", mime_content_type($file["tmp_name"]))[0] === "image" ? "image" : "video";

$sql = $conn->prepare("INSERT INTO chat_media (chat_id, media_path, media_type) VALUES (
    (SELECT id FROM chat WHERE client_id=? ORDER BY id DESC LIMIT 1),
    ?, ?
)");
$sql->execute([$client_id, $path, $type]);

echo "OK";
?>
