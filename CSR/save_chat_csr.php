<?php
session_start();
include "../db_connect.php";
include "b2_config.php";

header("Content-Type: application/json");

$sender_type = "csr";
$message     = trim($_POST["message"] ?? "");
$client_id   = (int)($_POST["client_id"] ?? 0);
$csr_name    = $_POST["csr_fullname"] ?? "";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "missing client"]);
    exit;
}

$media_path = null;
$media_type = null;

if (!empty($_FILES["files"]["name"][0])) {

    $file     = $_FILES["files"]["tmp_name"][0];
    $origName = $_FILES["files"]["name"][0];
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $newName  = time() . "_" . rand(1000,9999) . "." . $ext;

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
        $media_type = "image";
    } elseif (in_array($ext, ["mp4","mov","avi","mkv","webm"])) {
        $media_type = "video";
    }

    if ($media_type) {
        try {
            $result = $s3->putObject([
                "Bucket"      => $B2_BUCKET,
                "Key"         => "chat_media/" . $newName,
                "SourceFile"  => $file,
                "ACL"         => "public-read"
            ]);
            $media_path = $result["ObjectURL"];
        } catch (Exception $e) {
            echo json_encode(["status"=>"error", "msg"=>$e->getMessage()]);
            exit;
        }
    }
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
    VALUES (:cid, :stype, :msg, :mp, :mt, :csr, NOW())
");

$stmt->execute([
    ":cid"   => $client_id,
    ":stype" => $sender_type,
    ":msg"   => $message,
    ":mp"    => $media_path,
    ":mt"    => $media_type,
    ":csr"   => $csr_name
]);

echo json_encode(["status"=>"ok"]);
