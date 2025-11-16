<?php
session_start();
include '../db_connect.php';
header("Content-Type: application/json");

$message = trim($_POST["message"] ?? "");
$client_id = $_POST["client_id"] ?? 0;
$csr_fullname = $_POST["csr_fullname"] ?? "";

if(!$client_id){ echo json_encode(["status"=>"error"]); exit; }

$media_path=null; $media_type=null;

if(!empty($_FILES["file"]["name"])){
    $ext=strtolower(pathinfo($_FILES["file"]["name"],PATHINFO_EXTENSION));
    if(in_array($ext,['jpg','jpeg','png','gif','webp'])){ $media_type="image"; $folder="../uploads/chat_images/"; }
    else { $media_type="video"; $folder="../uploads/chat_videos/"; }
    $new=time()."_".rand(1000,9999).".".$ext;
    move_uploaded_file($_FILES["file"]["tmp_name"],$folder.$new);
    $media_path="uploads/".($media_type=="image"?"chat_images":"chat_videos")."/".$new;
}

$stmt=$conn->prepare("
  INSERT INTO chat (client_id,sender_type,message,media_path,media_type,csr_fullname,created_at)
  VALUES (:cid,'csr',:msg,:mp,:mt,:full,NOW())
");
$stmt->execute([":cid"=>$client_id,":msg"=>$message,":mp"=>$media_path,":mt"=>$media_type,":full"=>$csr_fullname]);

echo json_encode(["status"=>"ok"]);
