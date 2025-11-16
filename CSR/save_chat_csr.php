<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$message = trim($_POST["message"] ?? "");
$client_id = $_POST["client_id"] ?? 0;
$csr_fullname = $_POST["csr_fullname"] ?? "CSR";

if(!$client_id){
    echo json_encode(["status"=>"error"]);
    exit;
}

$media_path = null;
$media_type = null;

foreach($_FILES as $file){
    if($file['name']){
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if(in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
            $media_type="image";
            $folder="../upload/chat/";
        } elseif(in_array($ext, ["mp4","mov","mkv","avi","webm"])) {
            $media_type="video";
            $folder="../upload/chat/";
        }

        if($media_type){
            $newName = time()."_".rand(1000,9999).".".$ext;
            $path = $folder.$newName;
            move_uploaded_file($file["tmp_name"], $path);
            $media_path = "upload/chat/".$newName;
        }

        $conn->prepare("
            INSERT INTO chat (client_id, sender_type, message, media_path, media_type, csr_fullname, created_at)
            VALUES (:cid,'csr',:msg,:mp,:mt,:csr,NOW())
        ")->execute([
            ":cid"=>$client_id,
            ":msg"=>$message,
            ":mp"=>$media_path,
            ":mt"=>$media_type,
            ":csr"=>$csr_fullname
        ]);
    }
}

echo json_encode(["status"=>"ok"]);
?>
