<?php
session_start();
include "../db_connect.php";
date_default_timezone_set("Asia/Manila");

$client_id = $_POST["client_id"] ?? 0;
$message = $_POST["message"] ?? "";
$csr_fullname = $_POST["csr_fullname"] ?? "";

if (!$client_id) exit("error");

// Insert message first
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, message, sender_type, csr_fullname, created_at)
    VALUES (:cid, :msg, 'csr', :csr, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":msg" => $message,
    ":csr" => $csr_fullname
]);

$chat_id = $conn->lastInsertId();

// Upload media if has
if (!empty($_FILES["files"]["name"][0])) {

    foreach ($_FILES["files"]["tmp_name"] as $key => $tmp) {

        $name = uniqid() . "_" . $_FILES["files"]["name"][$key];
        $path = "upload/" . $name;

        move_uploaded_file($tmp, $path);

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $type = (in_array($ext, ["jpg","jpeg","png","gif","webp"])) ? "image" : "video";

        // insert media record
        $stmt2 = $conn->prepare("
            INSERT INTO chat_media (chat_id, media_path, media_type, created_at)
            VALUES (:cid, :path, :typ, NOW())
        ");
        $stmt2->execute([
            ":cid"  => $chat_id,
            ":path" => $path,
            ":typ"  => $type
        ]);
    }
}

echo "ok";
?>
