<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$message = trim($_POST["message"] ?? "");
$client_id = $_POST["client_id"];
$sender_type = "csr";
$csr_user = $_POST["csr_user"];
$csr_fullname = $_POST["csr_fullname"];

$file_path = null;
if (!empty($_FILES["file"]["name"])) {
    $name = time() . "_" . $_FILES["file"]["name"];
    $path = "upload/chat/" . $name;
    move_uploaded_file($_FILES["file"]["tmp_name"], $path);
    $file_path = $path;
}

$stmt = $conn->prepare("
    INSERT INTO chat (client_id, sender_type, message, file_path, assigned_csr, csr_fullname, created_at)
    VALUES(:cid, 'csr', :msg, :file, :csr, :full, NOW())
");
$stmt->execute([
    ":cid"=>$client_id,
    ":msg"=>$message,
    ":file"=>$file_path,
    ":csr"=>$csr_user,
    ":full"=>$csr_fullname
]);

echo json_encode(["status"=>"ok"]);
