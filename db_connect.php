<?php
$url = getenv("DATABASE_URL");

if (!$url) die("Database URL missing");

$conn = new PDO(
    $url,
    null,
    null,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);
?>
