<?php
require __DIR__ . '/../vendor/autoload.php';

use ChrisHarvey\BackblazeB2\Client;

$B2_KEY_ID      = getenv("B2_KEY_ID");
$B2_APP_KEY     = getenv("B2_APPLICATION_KEY");
$B2_BUCKET_NAME = getenv("B2_BUCKET_NAME");
$B2_BUCKET_URL  = getenv("B2_BUCKET_URL");

$b2 = new Client($B2_KEY_ID, $B2_APP_KEY);
