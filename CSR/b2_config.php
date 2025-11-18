<?php
require __DIR__ . "/../vendor/autoload.php";

use Aws\S3\S3Client;

$s3 = new S3Client([
    'version'     => 'latest',
    'region'      => 'us-east-1',
    'endpoint'    => 'https://s3.us-east-005.backblazeb2.com',
    'credentials' => [
        'key'    => '005a548887f9c4f0000000002', // KeyID
        'secret' => 'K005fOYaprINPto/Qdm9wex0w4v/L2k', // Application key
    ]
]);

$B2_BUCKET = 'ahba-chat-media';
?>
