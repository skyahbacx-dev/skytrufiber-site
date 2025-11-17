<?php
session_start();
include "../db_connect.php";
header("Content-Type: application/json");

$message  = trim($_POST["message"] ?? '');
$client_id = $_POST["client_id"] ?? 0;
$csr_fullname = $_POST["csr_fullname"] ?? 'CSR';

if (!$client_id) {
    echo json_encode(["status" => "error", "msg" => "No client"]);
    exit;
}

if ($message === "" && empty($_FILES['files']['name'][0])) {
    echo json_encode(["status" => "error", "msg" => "Empty message"]);
    exit;
}

$uploaded_files = [];

// Ensure upload folder exists
$upload_base = "../CSR/upload/chat/";
if (!file_exists($upload_base)) {
    mkdir($upload_base, 0777, true);
}

/*********** FILE UPLOAD MULTIPLE ***********/
if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $i => $fileName) {

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $media_type = "image";
        } elseif (in_array($ext, ['mp4','mov','avi','mkv','webm'])) {
            $media_type = "video";
        } else {
            continue; // skip unsupported file
        }

        $newName = time() . "_" . rand(1000,9999) . "." . $ext;
        $filePath = $upload_base . $newName;

        if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $filePath)) {
            $relativePath = "CSR/upload/chat/" . $newName;

            $uploaded_files[] = [
                "path" => $relativePath,
                "type" => $media_type
            ];
        }
    }
}

/*********** INSERT MAIN MESSAGE (IF ANY) ***********/
if ($message !== "" || empty($uploaded_files)) {
    $stmt = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, csr_fullname, message, created_at)
        VALUES (:cid, 'csr', :csr, :msg, NOW())
    ");
    $stmt->execute([
        ":cid" => $client_id,
        ":csr" => $csr_fullname,
        ":msg" => $message
    ]);
}

/*********** INSERT MEDIA RECORDS ***********/
foreach ($uploaded_files as $file) {
    $stmt2 = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, csr_fullname, media_path, media_type, created_at)
        VALUES (:cid, 'csr', :csr, :mp, :mt, NOW())
    ");
    $stmt2->execute([
        ":cid" => $client_id,
        ":csr" => $csr_fullname,
        ":mp"  => $file["path"],
        ":mt"  => $file["type"]
    ]);
}

echo json_encode(["status" => "ok"]);
exit;
?>
