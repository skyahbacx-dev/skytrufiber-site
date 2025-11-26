<?php
include "../db_connect.php";
session_start();

$csr       = $_SESSION["csr_user"] ?? "";
$client_id = $_POST["client_id"] ?? 0;
$message   = $_POST["message"] ?? "";

$sql = "INSERT INTO chat (client_id, sender_type, message) VALUES (:c, 'csr', :m) RETURNING id";
$stmt = $pdo->prepare($sql);
$stmt->execute([":c" => $client_id, ":m" => $message]);

$chatId = $stmt->fetchColumn();

if (!empty($_FILES["media"]["name"][0])) {
    foreach ($_FILES["media"]["tmp_name"] as $i => $tmp) {
        $filename = time() . "_" . $_FILES["media"]["name"][$i];
        $path = "../media/" . $filename;
        move_uploaded_file($tmp, $path);

        $media_type = str_contains($_FILES["media"]["type"][$i], "image") ? "image" : "video";

        $m = $pdo->prepare("INSERT INTO chat_media (chat_id, media_path, media_type) VALUES (:cid, :p, :t)");
        $m->execute([":cid" => $chatId, ":p" => $filename, ":t" => $media_type]);
    }
}

echo json_encode(["status" => "ok"]);
?>
