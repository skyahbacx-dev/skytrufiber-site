<?php
// thumbnail_backfill.php
require_once "../../db_connect.php";

ini_set("memory_limit", "512M");
ini_set("max_execution_time", 300);

echo "<h2>Generating thumbnails…</h2>";

$stmt = $conn->query("
    SELECT id, media_blob
    FROM chat_media
    WHERE media_type = 'image'
    AND thumb_blob IS NULL
");

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$items) {
    echo "<p>✔ No images found requiring thumbnails.</p>";
    exit;
}

$count = 0;

foreach ($items as $row) {
    $id = $row["id"];
    $blob = $row["media_blob"];

    if (is_resource($blob)) {
        $binary = stream_get_contents($blob);
    } else {
        $binary = $blob;
    }

    $image = @imagecreatefromstring($binary);
    if (!$image) {
        echo "<p>❌ Failed to decode image ID $id</p>";
        continue;
    }

    $maxWidth = 250;
    $thumb = @imagescale($image, $maxWidth);

    ob_start();
    imagejpeg($thumb, null, 70);
    $thumbBinary = ob_get_clean();

    imagedestroy($image);
    imagedestroy($thumb);

    $update = $conn->prepare("UPDATE chat_media SET thumb_blob = ? WHERE id = ?");
    $update->bindValue(1, $thumbBinary, PDO::PARAM_LOB);
    $update->bindValue(2, $id, PDO::PARAM_INT);
    $update->execute();

    $count++;
    echo "<p>✔ Created thumbnail for ID: $id</p>";
}

echo "<br><h3>🎉 Completed. Thumbnails generated: $count</h3>";
?>
