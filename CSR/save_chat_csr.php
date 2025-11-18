<?php
session_start();
require "../db_connect.php";
require "b2_config.php";

header("Content-Type: application/json");

$message      = trim($_POST["message"] ?? "");
$client_id    = (int)($_POST["client_id"] ?? 0);
$csr_fullname = $_POST["csr_fullname"] ?? "";
$sender_type  = "csr";

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "Invalid client"]);
    exit;
}

$media_path = null;
$media_type = null;

// HANDLE MULTIPLE FILES
if (!empty($_FILES["files"]["name"][0])) {
    $fileName = $_FILES["files"]["name"][0];
    $tmpFile  = $_FILES["files"]["tmp_name"][0];
    $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
        $media_type = "image";
    } elseif (in_array($ext, ["mp4","mov","avi","webm","mkv"])) {
        $media_type = "video";
    }

    if ($media_type) {
        $newName = time() . "_" . rand(1000,9999) . "." . $ext;

        try {
            $uploaded = $b2->upload([
                'BucketName' => $B2_BUCKET_NAME,
                'FileName'   => "chat_media/" . $newName,
                'Body'       => fopen($tmpFile, 'r')
            ]);

            $media_path = $B2_BUCKET_URL . "/chat_media/" . $newName;
        } catch (Exception $e) {
            echo json_encode(["status"=>"error", "msg"=>$e->getMessage()]);
            exit;
        }
    }
}

// INSERT CHAT MESSAGE
$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
    VALUES (:cid, :stype, :msg, :mp, :mt, :csr, NOW())
");
$stmt->execute([
    ":cid" => $client_id,
    ":stype" => $sender_type,
    ":msg" => $message,
    ":mp"  => $media_path,
    ":mt"  => $media_type,
    ":csr" => $csr_fullname
]);

echo json_encode(["status" => "ok"]);
