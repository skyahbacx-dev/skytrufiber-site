<?php
function b2_upload($fileTmp, $fileName)
{
    $bucketName  = "ahba-chat-media";
    $endpoint    = "https://s3.us-east-005.backblazeb2.com";
    $keyId       = "005a548887f9c4f0000000002";
    $appKey      = "K005fOYaprINPto/Qdm9wex0w4v/L2k";

    $url = "$endpoint/$bucketName/$fileName";

    $fp = fopen($fileTmp, "r");

    $headers = [
        "Authorization: AWS $keyId:$appKey",
        "x-amz-acl: public-read",
        "Content-Type: application/octet-stream"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_PUT, true);
    curl_setopt($ch, CURLOPT_INFILE, $fp);
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($fileTmp));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($status >= 200 && $status < 300) ? $url : null;
}
