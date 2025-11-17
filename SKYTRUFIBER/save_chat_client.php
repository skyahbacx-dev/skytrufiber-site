<?php
session_start();
include '../db_connect.php';
header('Content-Type: application/json');

$username = $_POST["username"] ?? '';
$message  = trim($_POST["message"] ?? '');
$sender   = "client";

// Prevent empty message + no file
if ($message === "" && empty($_FILES['files']['name'][0])) {
    echo json_encode(["status" => "error", "msg" => "Empty"]);
    exit;
}

// GET OR CREATE CLIENT RECORD
$stmt = $conn->prepare("SELECT id, assigned_csr FROM clients WHERE name = :name LIMIT 1");
$stmt->execute([":name" => $username]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $ins = $conn->prepare("INSERT INTO clients (name, created_at) VALUES (:n, NOW()) RETURNING id");
    $ins->execute([':n' => $username]);
    $client_id = $ins->fetchColumn();
} else {
    $client_id = $data["id"];
}

$uploaded_files = [];

// Ensure folders exist
$upload_base = "../CSR/upload/chat/";
if (!file_exists($upload_base)) {
    mkdir($upload_base, 0777, true);
}

// MULTIPLE FILE HANDLING
if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['name'] as $index => $fileName) {

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $media_type = "image";
        } elseif (in_array($ext, ['mp4','mov','avi','mkv','webm'])) {
            $media_type = "video";
        } else {
            continue;
        }

        // Save file
        $newName = time() . "_" . rand(1000,9999) . "." . $ext;
        $filePath = $upload_base . $newName;

        if (move_uploaded_file($_FILES['files']['tmp_name'][$index], $filePath)) {
            $relativePath = "CSR/upload/chat/" . $newName;

            $uploaded_files[] = [
                "path" => $relativePath,
                "type" => $media_type
            ];
        }
    }
}

// Insert text message if exists
if ($message !== "" || empty($uploaded_files)) {
    $stmt2 = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, message, created_at)
        VALUES (:cid, 'client', :msg, NOW())
    ");
    $stmt2->execute([
        ":cid" => $client_id,
        ":msg" => $message
    ]);
}

// Insert media files
foreach ($uploaded_files as $file) {
    $stmt3 = $conn->prepare("
        INSERT INTO chat (client_id, sender_type, media_path, media_type, created_at)
        VALUES (:cid, 'client', :mp, :mt, NOW())
    ");
    $stmt3->execute([
        ":cid" => $client_id,
        ":mp"  => $file["path"],
        ":mt"  => $file["type"]
    ]);
}

echo json_encode(["status" => "ok", "client_id" => $client_id]);
exit;
?>
