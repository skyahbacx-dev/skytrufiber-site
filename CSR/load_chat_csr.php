<?php
session_start();
require "../db_connect.php";
$client_id = $_POST["client_id"];

$q = $conn->prepare("SELECT * FROM chat WHERE client_id = ? ORDER BY created_at ASC");
$q->execute([$client_id]);
$messages = $q->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as $m) {
    $align = ($m["sender_type"] == "csr") ? "msg-csr" : "msg-client";

    echo "<div class='msg $align'>";
    echo nl2br($m["message"]);

    // Load media
    $media = $conn->prepare("SELECT * FROM chat_media WHERE chat_id=?");
    $media->execute([$m["id"]]);
    foreach ($media as $md) {
        if ($md["media_type"] == "image") {
            echo "<img src='{$md["media_path"]}' class='chat-img' onclick='openMedia(this.src)'>";
        } else {
            echo "<video class='chat-video' controls src='{$md["media_path"]}'></video>";
        }
    }

    echo "</div>";
}
?>