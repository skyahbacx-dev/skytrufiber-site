<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

$message      = trim($_POST["message"] ?? '');
$client_id    = $_POST["client_id"] ?? 0;
$csr_fullname = $_POST["csr_fullname"] ?? '';

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "no client"]);
    exit;
}

$uploadedFiles = $_FILES['files'] ?? null;

if (!$uploadedFiles && $message === '') {
    echo json_encode(["status" => "error", "msg" => "empty"]);
    exit;
}

if ($uploadedFiles) {
    for ($i=0; $i < count($uploadedFiles['name']); $i++) {

        $name = $uploadedFiles['name'][$i];
        $tmp  = $uploadedFiles['tmp_name'][$i];
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $media_type = null;
        $folder = null;

        if (in_array($ext,['jpg','jpeg','png','gif','webp'])) {
            $media_type = 'image';
            $folder     = "../CSR/upload/chat/";
        } elseif (in_array($ext,['mp4','mov','avi','mkv','webm'])) {
            $media_type = 'video';
            $folder     = "../CSR/upload/chat/";
        }

        if ($media_type) {
            $newName = time()."_".rand(1000,9999).".".$ext;
            $path = $folder.$newName;
            move_uploaded_file($tmp,$path);
            $media_path = "CSR/upload/chat/".$newName;

            $stmt = $conn->prepare("
                INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
                VALUES (:cid,'csr','',:mp,:mt,:csr,NOW())
            ");
            $stmt->execute([
                ":cid"=>$client_id,
                ":mp"=>$media_path,
                ":mt"=>$media_type,
                ":csr"=>$csr_fullname
            ]);
        }
    }
}

if ($message !== '') {
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, created_at, csr_fullname)
        VALUES (:cid,'csr',:msg,NOW(),:csr)
    ");
    $stmt->execute([
        ":cid"=>$client_id,
        ":msg"=>$message,
        ":csr"=>$csr_fullname
    ]);
}

echo json_encode(["status"=>"ok"]);
?>
